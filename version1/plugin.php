<?php
/*
Plugin Name: Device Details
Plugin URI: https://github.com/SachinSAgrawal/YOURLS-Device-Details
Description: Parses user-agent using a custom library to display information about IP and device
Version: 1.3.0
Author: Sachin Agrawal
Author URI: https://sachinagrawal.me
*/

defined('YOURLS_ABSPATH') || exit;

if (!class_exists('WhichBrowser\\Parser')) {
    $yourls_autoload = YOURLS_INC . '/vendor/autoload.php';
    if (file_exists($yourls_autoload)) {
        require_once $yourls_autoload;
    }
}

yourls_add_action('post_yourls_info_stats', 'device_details_render_table');

function device_details_get_ip_info($ip) {
    $url = "https://ipinfo.io/{$ip}/json";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) {
        return [];
    }

    $decoded = json_decode($response, true);

    return is_array($decoded) ? $decoded : [];
}

function device_details_get_timezone_offset($timezone) {
    try {
        $timezone_object = new DateTimeZone($timezone);
    } catch (Exception $exception) {
        return 0;
    }

    $datetime = new DateTime('now', $timezone_object);
    $offset = $timezone_object->getOffset($datetime);

    return $offset / 60; // Convert seconds to minutes
}

function device_details_timezone_offset_to_gmt_offset($timezone_offset) {
    $timezone_offset = intval($timezone_offset);
    $hours = floor($timezone_offset / 60);
    $offset = ($timezone_offset < 0 ? '-' : '+') . abs($hours);

    return 'GMT' . $offset;
}

function device_details_render_table($shorturl) {
    global $ydb;

    $keyword = is_array($shorturl) ? reset($shorturl) : $shorturl;
    $keyword = yourls_sanitize_keyword($keyword);

    $table_log = YOURLS_DB_TABLE_LOG;

    $sql = "SELECT * FROM `$table_log` WHERE shorturl = :keyword ORDER BY click_time DESC LIMIT 1000";
    $rows = $ydb->fetchObjects($sql, ['keyword' => $keyword]);

    if (!$rows) {
        return;
    }

    $outdata = '';
    foreach ($rows as $row) {
        $is_current_ip = isset($_SERVER['REMOTE_ADDR']) && $row->ip_address === $_SERVER['REMOTE_ADDR'];
        $current_ip_marker = $is_current_ip ? " bgcolor='#d4eeff'" : '';
        $current_ip_info = $is_current_ip ? '<br><i>this is your ip</i>' : '';

        $ua = $row->user_agent;
        $wbresult = class_exists('WhichBrowser\\Parser') ? new WhichBrowser\Parser($ua) : null;
        $ip_info = device_details_get_ip_info($row->ip_address);

        $timezone = $ip_info['timezone'] ?? null;
        $click_time_utc = device_details_resolve_click_datetime($row);
        $local_time = device_details_resolve_local_time($click_time_utc, $timezone);
        $gmt_offset = device_details_timezone_offset_to_gmt_offset($local_time['offset']);

        $outdata .= '<tr' . $current_ip_marker . '>'
            . '<td>' . device_details_escape_output($click_time_utc['display']) . '</td>'
            . '<td>' . device_details_escape_output($local_time['display']) . '</td>'
            . '<td>' . device_details_escape_output($gmt_offset) . '</td>'
            . '<td>' . device_details_escape_output($row->country_code ?? '') . '</td>'
            . '<td>' . device_details_escape_output($ip_info['city'] ?? '') . '</td>'
            . '<td><a href="https://who.is/whois-ip/ip-address/' . urlencode($row->ip_address) . '" target="_blank" rel="noopener">'
            . device_details_escape_output($row->ip_address) . '</a>' . $current_ip_info . '</td>'
            . '<td>' . device_details_escape_output($ua) . '</td>'
            . '<td>' . device_details_get_browser($wbresult) . '</td>'
            . '<td>' . device_details_get_os($wbresult) . '</td>'
            . '<td>' . device_details_escape_output(device_details_get_nested_value($wbresult, ['device', 'model'])) . '</td>'
            . '<td>' . device_details_escape_output(device_details_get_nested_value($wbresult, ['device', 'manufacturer'])) . '</td>'
            . '<td>' . device_details_escape_output(device_details_get_nested_value($wbresult, ['device', 'type'])) . '</td>'
            . '<td>' . device_details_escape_output(device_details_get_nested_value($wbresult, ['engine', 'name'])) . '</td>'
            . '<td>' . device_details_escape_output($row->referrer ?? '') . '</td>'
            . '</tr>';
    }

    echo '<table border="1" cellpadding="5" style="margin-top:25px;">'
        . '<tr><td width="80">Timestamp</td><td>Local Time</td><td>Timezone</td><td>Country</td><td>City</td>'
        . '<td>IP Address</td><td>User Agent</td><td>Browser Version</td><td>OS Version</td><td>Device Model</td>'
        . '<td>Device Vendor</td><td>Device Type</td><td>Engine</td><td>Referrer</td></tr>'
        . $outdata . '</table><br>';
}

function device_details_resolve_click_datetime($row) {
    $field = null;
    foreach (['click_time', 'timestamp', 'click_date', 'date'] as $candidate) {
        if (!empty($row->$candidate)) {
            $field = $candidate;
            break;
        }
    }

    $raw_value = $field ? $row->$field : '';

    try {
        $datetime = new DateTime($raw_value, new DateTimeZone('UTC'));
    } catch (Exception $exception) {
        $datetime = new DateTime('now', new DateTimeZone('UTC'));
    }

    return [
        'datetime' => $datetime,
        'display'  => $datetime->format('Y-m-d H:i:s'),
    ];
}

function device_details_resolve_local_time(array $click_time_utc, $timezone) {
    $timezone_offset = 0;
    $datetime = clone $click_time_utc['datetime'];

    if ($timezone) {
        $timezone_offset = device_details_get_timezone_offset($timezone);
        $datetime->modify($timezone_offset . ' minutes');
    }

    return [
        'datetime' => $datetime,
        'display'  => $datetime->format('Y-m-d H:i:s'),
        'offset'   => $timezone_offset,
    ];
}

function device_details_get_browser($wbresult) {
    if (!$wbresult) {
        return '';
    }

    $name = device_details_get_nested_value($wbresult, ['browser', 'name']);
    $version = device_details_get_nested_value($wbresult, ['browser', 'version', 'value']);

    return trim(device_details_escape_output($name . ' ' . $version));
}

function device_details_get_os($wbresult) {
    if (!$wbresult) {
        return '';
    }

    $name = device_details_get_nested_value($wbresult, ['os', 'name']);
    $version = device_details_get_nested_value($wbresult, ['os', 'version', 'value']);

    return trim(device_details_escape_output($name . ' ' . $version));
}

function device_details_get_nested_value($object, array $path) {
    $current = $object;
    foreach ($path as $segment) {
        if (is_object($current) && isset($current->$segment)) {
            $current = $current->$segment;
        } else {
            return '';
        }
    }

    return is_scalar($current) ? $current : (string) $current;
}

function device_details_escape_output($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
