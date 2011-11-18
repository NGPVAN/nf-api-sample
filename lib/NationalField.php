<?php

class NationalField {

    protected $key;
    protected $secret;

    protected $session_key = 'nf_apisample';

    public function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    public function setClient($client)
    {
        $this->setSessionValue('client', $client);
    }

    public function authenticate()
    {
        $authUrl = $this->getAuthBaseUrl() . '/authenticate' .
                   '?client_id=' . urlencode($this->key) .
                   '&response_type=code' .
                   '&redirect_uri=' . urlencode($this->getRedirectUri());

        $this->redirect($authUrl);
    }

    public function requestToken($authorizationCode)
    {
        $authUrl = $this->getAuthBaseUrl() . '/access_token';
        $params = array(
           'client_id' => $this->key,
           'client_secret' => $this->secret,
           'grant_type' => 'authorization_code',
           'code' => $authorizationCode,
           'redirect_uri' => $this->getRedirectUri()
        );

        $json = $this->makeJsonRequest($authUrl, $params);

        if ($json && isset($json['access_token'])) {
            $this->setSessionValue('authenticated', true);
            $this->setSessionValue('access_token', $json['access_token']);
            return true;
        }

        return false;
    }
    
    public function clearAuthentication()
    {
        $this->setSessionValue('authenticated', false);
        $this->setSessionValue('access_token', null);
    }

    public function isAuthenticated()
    {
        $authenticated = $this->getSessionValue('authenticated');
        return ($authenticated === true);
    }

    public function api($resource, $params = array(), $method = 'GET')
    {
        // all calls require auth
        $params['access_token'] = $this->getSessionValue('access_token');

        $url = $this->getApiBaseUrl() . '/' . $resource;
        return $this->makeJsonRequest($url, $params, $method);
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

    protected function getAuthBaseUrl()
    {
        return 'http://' . $this->getHostname() . '/frontend_dev.php/oauth';

    }

    protected function getApiBaseUrl()
    {
        return 'http://' . $this->getHostname() . '/rest_dev.php';
    }

    protected function getHostname()
    {
        return $this->getSessionValue('client') . '.nationalfield.localhost';
    }

    protected function getRedirectUri($action = null)
    {
        $uri = (isset($_SERVER['HTTPS']) ? 'https' : 'http') .
            '://' .
            $_SERVER['HTTP_HOST'] .
            $_SERVER['PHP_SELF'];
        return $uri;
    }

    protected function makeJsonRequest($url, $params = null, $method = 'GET')
    {
        $raw = $this->makeRequest($url, $params, $method);
        return json_decode($raw, true);
    }

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