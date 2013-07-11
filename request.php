<?php

class Request {

    //default app config variables
    protected $config = array(
        'base_url' => 'https://api.twitter.com/',
        'consumer_key' => '',
        'consumer_secret' => '',
        'oauth_callback' => '',
        'oauth' => array(),
        'oauth_type' => 'authorized'
    );
    protected $requestUrl = '';
    protected $requestMethod = ''; // POST or GET
    protected $requestData = array();
    protected $oauthAccessToken = ''; // user access token
    protected $oauthAccessTokenSecret = ''; // user access token secret
    protected $oauthToken = '';

    /**
     * 
     * @param array $config
     */
    public function __construct($config = array()) {
        //overite defult config variables
        foreach ($config as $key => $value) {
            $this->config[$key] = $value;
        }
    }

    /**
     * Set oauth_teken used to verify user befor getting the auth access token 
     * 
     * @param type $oauthToken
     * @return \REQUEST
     */
    public function setOauthToken($oauthToken) {
        $this->oauthToken = $oauthToken;
        return $this;
    }

    /**
     * set user oauth credential used to make api request 
     * 
     * @param string $oauthAccessToken
     * @param string $oauthAccessTokenSecret
     * @return \REQUEST
     */
    public function setUserCredentials($oauthAccessToken, $oauthAccessTokenSecret) {
        $this->oauthAccessToken = $oauthAccessToken;
        $this->oauthAccessTokenSecret = $oauthAccessTokenSecret;
        return $this;
    }

    /**
     * Set Request info as the request url
     * or request method that is GET or POST cupitalized
     * and an array of data depending on the request
     * 
     * @param string $requestUrl
     * @param string $requestMethod
     * @param array $requestData
     * @return \REQUEST
     */
    public function setRequestInfo($requestUrl, $requestMethod, $requestData = array()) {
        $this->requestUrl = $requestUrl;
        $this->requestMethod = $requestMethod;
        $this->requestData = $requestData;
        //add GET data to url
        return $this;
    }

    /**
     * add requred variables to $this->config['oauth'] that need to be used
     * on the rquest process 
     * 
     * @return \REQUEST
     */
    public function buildRequest() {

        $this->config['oauth']['oauth_consumer_key'] = $this->config['consumer_key'];
        $this->config['oauth']['oauth_nonce'] = md5(uniqid(rand(), true));
        $this->config['oauth']['oauth_signature_method'] = 'HMAC-SHA1';
        $this->config['oauth']['oauth_timestamp'] = time();
        $this->config['oauth']['oauth_version'] = '1.0';

        //oauth fist step of authorization
        if ($this->config['oauth_type'] == 'unauthorized') {
            $this->config['oauth']['oauth_callback'] = $this->config['oauth_callback'];
            $this->config['oauth']['oauth_signature'] = $this->buildSignature();
        } else if ($this->config['oauth_type'] == 'semi-authorized') {
            $this->config['oauth']['oauth_token'] = $this->oauthToken;
            $this->config['oauth']['oauth_signature'] = $this->buildSignature();
        } else {//request by an oauth user
            $this->config['oauth']['oauth_token'] = $this->oauthAccessToken;
            $this->config['oauth']['oauth_signature'] = $this->buildSignature();
        }

        return $this;
    }

    /**
     * make the API call and return its response
     * 
     * @return json respons
     */
    public function makeRequest() {

        $url = $this->config['base_url'] . $this->requestUrl;

        $header = $this->authorizationHeader();

        $ch = curl_init();
        if ($this->requestMethod == 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getRequestData());
        } else {
            if (!empty($this->requestData)) {
                $url .= '?' . $this->getRequestData();
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * return a encoded string used on request body if the request is a POST request
     * or on the url part if it is an GET request
     * 
     * @return string
     */
    protected function getRequestData() {
        $return = array();
        foreach ($this->requestData as $key => $value) {
            $return[] = rawurlencode($key) . '=' . rawurlencode($value);
        }
        return implode('&', $return);
    }

    /**
     * Build the authoriazed signature used on header part of the request
     * 
     * @return string
     */
    protected function buildSignature() {

        $base_info = $this->encodeRequestData();

        if ($this->config['oauth_type'] == 'unauthorized') {
            $composite_key = rawurlencode($this->config['consumer_secret']) . "&";
        } else if ($this->config['oauth_type'] == 'semi-authorized') {
            $composite_key = rawurlencode($this->config['consumer_secret']) . '&' . rawurlencode($this->oauthAccessToken);
        } else {
            $composite_key = rawurlencode($this->config['consumer_secret']) . '&' . rawurlencode($this->oauthAccessTokenSecret);
        }

        return base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
    }

    /**
     * create an encoded string usung request method eather POST or GET,
     * the full request url and all the data from config->oauth + requestData
     * 
     * @return string
     */
    protected function encodeRequestData() {
        $return = array();
        //get oauth data + request data if there are any
        $urlData = (!empty($this->requestData)) ? $this->config['oauth'] + $this->requestData : $this->config['oauth'];
        //sort by key name
        uksort($urlData, 'strcmp');

        foreach ($urlData as $key => $value) {
            $return[] = rawurlencode($key) . '=' . rawurlencode($value);
        }

        return $this->requestMethod . "&" . rawurlencode($this->config['base_url'] . $this->requestUrl) . '&' . rawurlencode(implode('&', $return));
    }

    /**
     * reutn the authorized header used on the request
     * 
     * @return array
     */
    protected function authorizationHeader() {

        $return = 'Authorization: OAuth ';
        $values = array();
        //sort by key name
        uksort($this->config['oauth'], 'strcmp');

        foreach ($this->config['oauth'] as $key => $value) {
            $values[] = "$key=\"" . rawurlencode($value) . "\"";
        }

        $return .= implode(', ', $values);

        return array(
            "X-HostCommonName: api.twitter.com",
            $return,
            "Host: api.twitter.com",
            "X-Target-URI: https://api.twitter.com",
            "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
            "Connection: Keep-Alive",
            "Expect:"
        );
    }

}

?>