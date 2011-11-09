<?php

class NationalField {

    protected $key;
    protected $secret;

    protected $session_key = 'nf_apisample';

    public function __construct($key, $secret) {
        $this->key = $key;
        $this->secret = $secret;
    }

    public function requestAuthorization() {
        $authUrl = 'http://' . $this->getSessionValue('client') . '.nationalfield.localhost/frontend_dev.php/oauth/authenticate' .
                   '?client_id=' . urlencode($this->key) .
                   '&response_type=code' .
                   '&redirect_uri=' . urlencode($this->getRedirectUri());

        $this->redirect($authUrl);
    }

    public function requestToken($authorizationCode) {
        $authUrl = 'http://' . $this->getSessionValue('client') . '.nationalfield.localhost/frontend_dev.php/oauth/access_token' .
                   '?client_id=' . urlencode($this->key) .
                   '&client_secret=' . urlencode($this->secret) .
                   '&grant_type=authorization_code' .
                   '&code=' . urlencode($authorizationCode) .
                   '&redirect_uri=' . urlencode($this->getRedirectUri());

        $json = $this->getJson($authUrl);

        if ($json && isset($json['access_token'])) {
            $this->setSessionValue('authenticated', true);
            $this->setSessionValue('access_token', $json['access_token']);
            return true;
        }

        return false;
    }

    public function isAuthenticated() {
        $authenticated = $this->getSessionValue('authenticated');
        return ($authenticated === true);
    }

    public function setClient($client) {
        $this->setSessionValue('client', $client);
    }

    protected function initSession() {
        if (session_id() == '') {
            session_start();
        }
        if (!isset($_SESSION[$this->session_key])) {
            $_SESSION[$this->session_key] = array();
        }
    }

    protected function getSessionValue($key) {
        $this->initSession();
        if (isset($_SESSION[$this->session_key][$key])) {
            return $_SESSION[$this->session_key][$key];
        }
        return null;
    }

    protected function setSessionValue($key, $value) {
        $this->initSession();
        $_SESSION[$this->session_key][$key] = $value;
    }

    protected function getRedirectUri($action = null) {
        $uri = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' .
            $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
        return $uri;
    }

    protected function getJson($url) {
        $raw = file_get_contents($url);
        return json_decode($raw, true);
    }

    protected function redirect($url) {
        header('location: ' . $url);
        exit;
    }



}