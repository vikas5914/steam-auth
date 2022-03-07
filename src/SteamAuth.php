<?php

namespace Vikas5914;

use Vikas5914\Exceptions\ApiKeyNotFoundException;

/**
 * SteamAuth Class.
 *
 * @author    Vikas Kapadiya <vikas@kapadiya.net>
 * @author    BlackCetha
 * @author    Alexandre Candeias <alexandreluisbarreto@gmail.com>
 * @license   https://opensource.org/licenses/MIT The MIT License
 *
 * @link      https://github.com/vikas5914/steam-auth
 *
 * @version   1.0.5
 */
class SteamAuth
{
    /**
     * Steam Web API key.
     *
     * @link http://steamcommunity.com/dev/apikey
     *
     * @var string|null
     */
    protected $apiKey = null;

    /**
     * Your website's domain.
     *
     * @var string|null
     */
    protected $domainName = null;

    /**
     * Your website's login page URL.
     * Returns to last page if not set.
     *
     * @var string|null
     */
    protected $loginPage = null;

    /**
     * Your website's logout page URL
     *
     * @var string|null
     */
    protected $logoutPage = null;

    /**
     * Whether to retrieve data from steam or just steamId64.
     *
     * @var boolean
     */
    protected $skipApi = false;

    /**
     * Constructor
     *
     * @param string|null $apiKey
     * @param string|null $domainName
     * @param string|null $loginPage
     * @param string|null $logoutpage
     * @param boolean $skipApi (default: false)
     * @throws ApiKeyNotFoundException
     */
    public function __construct($apiKey = null, $domainName = null, $loginPage = null, $logoutPage = null, $skipApi = false)
    {
        if (empty(session_id())) {
            // Start the session if it hasn't been started yet
            // and destroys the previous one, just in case some cache is laying around.
            session_destroy();
            session_start();
        }

        $this->apiKey = getenv('STEAM_AUTH_API_KEY') ?: $apiKey;
        $this->domainName = getenv('STEAM_AUTH_DOMAIN_NAME') ?: $domainName;
        $this->loginPage = getenv('STEAM_AUTH_LOGIN_PAGE') ?: $loginPage;
        $this->logoutPage = getenv('STEAM_AUTH_LOGOUT_PAGE') ?: $logoutPage;
        $this->skipApi = getenv('STEAM_AUTH_SKIP_API') ?: $skipApi;

        if (empty($this->apiKey)) {
            throw new ApiKeyNotFoundException('Steam API Key Not Found');
        }

        // If there isn't any loginPage set, we use the current page
        if (empty($this->loginPage)) {
            $this->loginPage = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
        }

        // Code (c) 2010 ichimonai.com, released under MIT-License
        if (isset($_GET['openid_assoc_handle']) && !isset($_SESSION['steamdata']['steamid'])) {
            // Did we just return from steam login-page? If so, validate identity and save the data
            $steamid = $this->validate();
            if (!empty($steamid)) {
                // ID Proven, get data from steam and save them
                if ($this->skipApi) {
                    $_SESSION['steamdata']['steamid'] = $steamid;

                    return; // Skip API here
                }

                $apiresp = $this->getPlayerData($steamid);
                $_SESSION['steamdata'] = $apiresp['response']['players'][0];
            }
        }

        if (isset($_SESSION['steamdata']) && !empty($_SESSION['steamdata'])) {
            // If we are logged in, make user-data accessable through $steam->var
            foreach ($_SESSION['steamdata'] as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Generate SteamLogin-URL.
     *
     * @copyright loginUrl function (c) 2010 ichimonai.com, released under MIT-License
     * Modified by BlackCetha for OOP use
     *
     * @return string
     */
    public function loginUrl()
    {
        $params = [
            'openid.ns'         => 'http://specs.openid.net/auth/2.0',
            'openid.mode'       => 'checkid_setup',
            'openid.return_to'  => $this->loginPage,
            'openid.realm'      => (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'],
            'openid.identity'   => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        ];

        return 'https://steamcommunity.com/openid/login?' . http_build_query($params, '', '&');
    }

    /**
     * Validate data against Steam-Servers
     * @copyright validate function (c) 2010 ichimonai.com, released under MIT-License
     * Modified by BlackCetha for OOP use
     *
     * @return int|false Returns steamId on success, false otherwise
     */
    private static function validate()
    {
        // Star off with some basic params
        $params = [
            'openid.assoc_handle' => $_GET['openid_assoc_handle'],
            'openid.signed'       => $_GET['openid_signed'],
            'openid.sig'          => $_GET['openid_sig'],
            'openid.ns'           => 'http://specs.openid.net/auth/2.0',
        ];

        // Get all the params that were sent back and resend them for validation
        $signed = explode(',', $_GET['openid_signed']);
        foreach ($signed as $item) {
            $val = $_GET['openid_' . str_replace('.', '_', $item)];
            $params['openid.' . $item] = stripslashes($val);
        }

        // Finally, add the all important mode.
        $params['openid.mode'] = 'check_authentication';

        // Stored to send a Content-Length header
        $data = http_build_query($params);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Accept-language: en\r\n" .
                    "Content-type: application/x-www-form-urlencoded\r\n" .
                    'Content-Length: ' . strlen($data) . "\r\n",
                'content' => $data,
            ],
        ]);

        $result = file_get_contents('https://steamcommunity.com/openid/login', false, $context);

        // Validate wheather it's true and if we have a good ID
        preg_match('#^https://steamcommunity.com/openid/id/([0-9]{17,25})#', $_GET['openid_claimed_id'], $matches);
        $steamID64 = is_numeric($matches[1]) ? $matches[1] : 0;

        // Return our final value
        return preg_match("#is_valid\s*:\s*true#i", $result) == 1 ? $steamID64 : false;
    }

    /**
     * Logs the user out of the website.
     *
     * @return bool
     */
    public function logout()
    {
        if (!$this->loggedIn()) {
            return false;
        }

        unset($_SESSION['steamdata']); // Delete the users info from the cache, DOESNT DESTROY YOUR SESSION!
        if (!isset($_SESSION[0])) {
            session_destroy();
        }

        // End the session if theres no more data in it
        if (!empty($this->logoutPage)) {
            header('Location: ' . $this->logoutPage);
        }

        // If the logout-page is set, go there
        return true;
    }

    /**
     * Checks whether the current user is logged in.
     *
     * @return bool
     */
    public function loggedIn()
    {
        return (isset($_SESSION['steamdata']['steamid']) && !empty($_SESSION['steamdata']['steamid']));
    }

    /**
     * Reloads the user's steam data
     *
     * @return bool
     */
    public function forceReload()
    {
        if (!isset($_SESSION['steamdata']['steamid']) && empty($_SESSION['steamdata']['steamid'])) {
            return false;
        }

        // User data should not be reloaded if skipApi is set to true
        if ($this->skipApi) {
            return true;
        }

        $apiresp = $this->getPlayerData($_SESSION['steamdata']['steamid']);
        $_SESSION['steamdata'] = $apiresp['response']['players'][0];
        
        foreach ($_SESSION['steamdata'] as $key => $value) {
            $this->{$key} = $value;
        }

        // Make user-data accessable through $steam->var
        return true;
    }

    /**
     * Prints debug information about steamauth.
     *
     * @return void
     */
    public function debug()
    {
        echo '<h1>SteamAuth debug report</h1><hr><b>Settings-array:</b><br>';
        echo '<pre>' . print_r($this->settings, true) . '</pre>';
        echo '<br><br><b>Data:</b><br>';
        echo '<pre>' . print_r($_SESSION['steamdata'], true) . '</pre>';
    }

    /**
     * Retrieves the user's profile data given a steamid
     *
     * @param string $steamid
     * @return array
     */
    public function getPlayerData($steamid)
    {
        return @json_decode(
            file_get_contents('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $this->apiKey . '&steamids=' . $steamid),
            true
        );
    }
}
