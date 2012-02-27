<?php

/**
 * Helper class for the NationalField API
 */
class NationalField {

    /**
     * The OAuth client key
     *
     * @var string
     */
    protected $key;

    /**
     * The OAuth client secret
     *
     * @var string
     */
    protected $secret;

    /**
     * Current user id
     *
     * @var integer
     */
    protected $user_id;

    /**
     * Session storage key
     *
     * @var string
     */
    protected $session_key = 'nf_api';

    public static $baseDomain = 'nationalfield.com';
    public static $apiPath = '/api/v1';
    public static $frontendPath = '';

    /**
     * Constructor
     *
     * @param string $key Oauth client key
     * @param string $secret Oauth client secret
     * @param string $session_suffix Addition to the session key to allow
     *                               multiple applications using the same store
     */
    public function __construct($key, $secret, $session_suffix = null)
    {
        $this->key = $key;
        $this->secret = $secret;

        if (is_string($session_suffix)) {
            $this->session_key .= '_' . $session_suffix;
        }
    }

    // Configuration

    /**
     * Set the NationalField client name
     *
     * @param string $client
     */
    public function setClient($client)
    {
        $this->setSessionValue('client', $client);
    }

    /**
     * Get the NationalField client name
     *
     * @return string
     */
    public function getClient()
    {
        return $this->getSessionValue('client');
    }

    /**
     * Set the application URI
     *
     * Used for redirects. If not set, the script's URL will be used.
     *
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->setSessionValue('uri', $uri);
    }

    /**
     * Get the application URI
     *
     * @return string
     */
    public function getUri()
    {
        return $this->getSessionValue('uri');
    }

    // Authentication/Authorization

    /**
     * Get the ID of the authenticated user
     * 
     * Checks local cache, session, and signed params.
     *
     * @return integer
     */
    public function getUserId()
    {
        if (!is_null($this->user_id)) {
            return $this->user_id;
        }

        return $this->user_id = $this->detectUser();
    }

    /**
     * Check is there is an authenticated user
     *
     * @return boolean
     */
    public function isAuthenticated()
    {
        return (!is_null($this->getUserId()));
    }

    /**
     * Get the NationalField URL for authentication
     *
     * @return string
     */
    public function getAuthenticationUrl()
    {
        return $this->getAuthBaseUrl() . '/authenticate' .
               '?client_id=' . urlencode($this->key) .
               '&response_type=code' .
               '&redirect_uri=' . urlencode($this->getRedirectUri());
    }

    /**
     * Redirect to NationalField for authentication
     */
    public function redirectForAuthentication()
    {
        $authUrl = $this->getAuthenticationUrl();

        $this->redirect($authUrl);
    }

    /**
     * Exchange the authorization code for an access token
     *
     * @param string $authorizationCode
     * @return boolean
     */
    public function completeAuthentication($authorizationCode)
    {
        // get access token
        $authUrl = $this->getAuthBaseUrl() . '/access_token';
        $params = array(
           'client_id' => $this->key,
           'client_secret' => $this->secret,
           'grant_type' => 'authorization_code',
           'code' => $authorizationCode,
           'redirect_uri' => $this->getRedirectUri()
        );

        $json = $this->makeJsonRequest($authUrl, $params);

        // if succesfull, get user id with 'me' call
        if ($json && isset($json['access_token'])) {
            $this->setSessionValue('access_token', $json['access_token']);

            if ($me = $this->api('users/me')) {
                $this->setSessionValue('user_id', $me['id']);
                return true;
            } else {
                $this->clearAuthentication();
            }
        }

        return false;
    }

    /**
     * Clear authentication from the session
     */
    public function clearAuthentication()
    {
        $this->setSessionValue('user_id', null);
        $this->setSessionValue('access_token', null);
    }

    // API Calls

    /**
     * Call the NationalField API
     *
     * Uses the stored access token
     *
     * @param string $resource Path to the resource
     * @param array $params Parameters to send
     * @param string $method HTTP method
     * @return mixed array or false
     */
    public function api($resource, $params = array(), $method = 'GET')
    {
        // all calls require auth
        $params['access_token'] = $this->getSessionValue('access_token');

        $url = $this->getApiBaseUrl() . '/' . $resource;
        return $this->makeJsonRequest($url, $params, $method);
    }

    // Internal methods

    /**
     * Determine the authenticated user
     *
     * First, check for signed parameters. If so, use those, and clear authentication
     * if they don't match. Second, use the session value.
     *
     * @return integer
     */
    protected function detectUser()
    {
        $params = $this->decodeParams();
        if ($params) {
            $this->setClient($params['client']);
            $this->setUri($params['url']);

            if (isset($params['user_id']) && isset($params['access_token'])) {
                $this->setSessionValue('access_token', $params['access_token']);
                $this->setSessionValue('user_id', $params['user_id']);
            } else {
                $this->clearAuthentication();
                return null;
            }
        }

        return $this->getSessionValue('user_id');
    }

    /**
     * Decode the signed parameters (if present)
     *
     * Checks the signature and returns false if not matching. Otherwise
     * returns the decoded json array.
     *
     * @return mixed
     */
    protected function decodeParams()
    {
        if (isset($_POST['signed_params'])) {
            list($encodedSignature, $encodedJson) = explode('.', $_POST['signed_params'], 2);

            $signature = base64_decode($encodedSignature);
            $json = base64_decode($encodedJson);

            if ($signature != hash_hmac('sha256', $json, $this->secret, true)) {
                return false;
            }

            $params = json_decode($json, true);

            if (json_last_error() == JSON_ERROR_NONE) {
                return $params;
            }
        }

        return false;
    }
    
    protected function initSession()
    {
        if (session_id() == '') {
            session_start();
        }
        if (!isset($_SESSION[$this->session_key])) {
            $_SESSION[$this->session_key] = array();
        }
    }

    protected function getSessionValue($key)
    {
        $this->initSession();
        if (isset($_SESSION[$this->session_key][$key])) {
            return $_SESSION[$this->session_key][$key];
        }
        return null;
    }

    protected function setSessionValue($key, $value)
    {
        $this->initSession();
        $_SESSION[$this->session_key][$key] = $value;
    }

    protected function clearSession()
    {
        $this->initSession();
        unset($_SESSION[$this->session_key]);
    }

    protected function getAuthBaseUrl()
    {
        return 'http://' . $this->getHostname() . self::$frontendPath . '/oauth';
    }

    protected function getApiBaseUrl()
    {
        return 'http://' . $this->getHostname() . self::$apiPath;
    }

    protected function getHostname()
    {
        return $this->getSessionValue('client') . '.' . self::$baseDomain;
    }

    protected function getRedirectUri()
    {
        $uri = $this->getUri();
        if (is_null($uri))
        {
            $uri = (isset($_SERVER['HTTPS']) ? 'https' : 'http') .
                   '://' .
                   $_SERVER['HTTP_HOST'] .
                   $_SERVER['PHP_SELF'];
            $this->setUri($uri);
        }
        return $uri;
    }

    /**
     * Make an HTTP request and process the result as JSON
     *
     * @param string $url
     * @param array $params Parameters to send
     * @param string $method HTTP method
     * @return mixed array or false
     */
    protected function makeJsonRequest($url, $params = null, $method = 'GET')
    {
        $raw = $this->makeRequest($url, $params, $method);
        $response = json_decode($raw, true);

        if (json_last_error() == JSON_ERROR_NONE) {
            return $response;
        }

        return false;
    }

    /**
     * Make an HTTP request
     *
     * @param string $url
     * @param array $params Parameters to send
     * @param string $method HTTP method
     * @return string
     */
    protected function makeRequest($url, $params = null, $method = 'GET')
    {
        $method = strtoupper($method);

        $ch = curl_init();

        switch($method)
        {
            case 'GET':
                if (!is_null($params)) $url .= '?' . http_build_query($params);
                break;
            
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if (!is_null($params)) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                break;
            
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if (!is_null($params)) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                break;
            
            case 'DELETE':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!is_null($params)) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                break;

            default:
                throw new Exception('Unsupported method "' . $method . '"');
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        return curl_exec($ch);
    }

    protected function redirect($url)
    {
        header('location: ' . $url);
        exit;
    }
}