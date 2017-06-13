# Steam authentication and User Details
[![Latest Stable Version](https://poser.pugx.org/vikas5914/steam-auth/v/stable)](https://packagist.org/packages/vikas5914/steam-auth) [![Total Downloads](https://poser.pugx.org/vikas5914/steam-auth/downloads)](https://packagist.org/packages/vikas5914/steam-auth) [![License](https://poser.pugx.org/vikas5914/steam-auth/license)](https://packagist.org/packages/vikas5914/steam-auth) [![GitHub issues](https://img.shields.io/github/issues/vikas5914/steam-auth.svg)](https://github.com/vikas5914/steam-auth/issues) [![Packagist](https://img.shields.io/packagist/dd/vikas5914/steam-auth.svg)](https://packagist.org/packages/vikas5914/steam-auth) 

This package enables you to easily log users in via Steam and get user details , using their OpenID service. However, this package does not require that you have the OpenID PHP module installed!

## Installation Via Composer

Add this to your `composer.json` file, in the require object:

```javascript
"vikas5914/steam-auth": "1.*"
```

After that, run `composer install` to install the package.
#### OR
```javascript
composer require vikas5914/steam-auth:1.*
```
## Usage example

```php
require __DIR__ . '/vendor/autoload.php';

$config = array(
    'apikey' => 'xxxxxxxxxxxxxxxxx', // Steam API KEY
    'domainname' => 'http://localhost:3000', // Displayed domain in the login-screen
    'loginpage' => 'http://localhost:3000/index.php', // Returns to last page if not set
    "logoutpage" => "",
    "skipAPI" => false, // true = dont get the data from steam, just return the steamid64
);

$steam = new Vikas5914\SteamAuth($config);

if ($steam->loggedIn()) {
    echo "Hello " . $steam->personaname . "!";
    echo "<a href='" . $steam->logout() . "'>Logout</a>";
} else {
    echo "<a href='" . $steam->loginUrl() . "'>Login</a>";
}
```

User-Data is accessible through `$steam->varName;` You can find a basic list of variables in the demo file or a more advanced one in the code.

Check if the user is logged in with `$steam->loggedIn();` (Will return true or false)

## Planned
 1. Test Case
 2. Better ReadMe

## Legal stuff

If you choose to use the steam web-api you need to follow the Steam Web API Terms of Use found at http://steamcommunity.com/dev/apiterms

The marked code is taken from Syntax_Error's "Ultra Simple Steam-Login" Class found at http://forums.steampowered.com/forums/showthread.php?t=1430511


[![forthebadge](http://forthebadge.com/images/badges/you-didnt-ask-for-this.svg)](http://forthebadge.com)
