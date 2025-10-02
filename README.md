

# Device Details [![Listed in Awesome YOURLS!](https://img.shields.io/badge/Awesome-YOURLS-C5A3BE)](https://github.com/YOURLS/awesome-yourls/)

**Fixed only Version 1**

A plugin to display information about each click, based on Tacoded's IP Click Details. <br>
Requires Your Own URL Shortener ([YOURLS](https://yourls.org)) `v1.9.2` and above.

## Fork Information

This repository is a fork of the original [YOURLS Device Details](https://github.com/SachinSAgrawal/YOURLS-Device-Details) plugin by Sachin Agrawal.  
The primary purpose of this fork is to fix database method compatibility with YOURLS versions 1.7.3 and newer by replacing deprecated calls with PDO-based queries.
This was quickly coded with ChatGPT-Codex. 

## Usage

![screenshot](screenshot.png)

Check it out on my own website: [ipgrabber.pro](https://ipgrabber.pro). I use Sleeky's front-end, but the default should work.

## Version 1
This plugin uses WhichBrowser's Parser to display IP, user-agent, device, browser, location, and time.
**Updated database calls to use PDO methods instead of deprecated get_var. Tested with YOURLS 1.10.2.**

## Version 2
**THIS IS NOT FIXED**
In addition to everything in version 1, as promised, this update provides more tracked stats.  <br>
Things like device battery, orientation, language, and screen info can be logged with some Javascript.  <br>
However, for this to work, the `functions.php` file needs to be modified, which is not recommended.  <br>
In fact, "hacking" core files is essentially [banned](https://yourls.org/docs/development/dont-hack-core), but I do not know how to do it otherwise. <br>
My modifications are not anything massive. Feel free to check them out on [diffchecker.com](https://www.diffchecker.com/UvnSxpDU/) if desired. <br>
I have also not really tested this super rigorously, so if you find any bugs, feel free to open an issue! <br>
I'm aware that by using cookies to store the data, the data will always be one click behind. 


## Installation
1. Install WhichBrowser to the root using `composer require whichbrowser/parser`.
2. It is possible that you will have to change the path of `vendor/autoload.php`.
3. In `/user/plugins`, create a new folder named `device-details`.
4. Choose what version you want (see the two sections above to decide).
5. Download the right `plugin.php` file from this git repo and drop it into step 3's directory.
6. If you use version 2, make sure to also replace `/includes/functions.php` with the provided one.
7. Go to the Plugins admin page (eg. `http://sho.rt/admin/plugins.php`) and activate it.
8. Even if the admin area is private, you should make the link stats page public.
9. Do this by adding `define('YOURLS_PRIVATE_INFOS', false);` to `config.php`.

I got a request to make this compatible with the [Download Plugin](https://github.com/krissss/yourls-download-plugin) plugin, so I will look more into it. Version 1 should be compatible, although it looks like I need to configure the branches of this repository a little bit differently.

## Contributors
Sachin Agrawal: I'm a self-taught programmer who knows many languages and I'm into app, game, and web development. For more information, check out my website or Github profile. If you would like to contact me, my email is [github@sachin.email](mailto:github@sachin.email).

## License
This package is licensed under the [MIT License](LICENSE.txt).
