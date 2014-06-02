<?php
/*
 * Abraham Williams (abraham@abrah.am) http://abrah.am
 *
 * The first PHP Library to support OAuth for Twitter's REST API.
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/* Load OAuth lib. You can find it at http://oauth.net */
require_once(APPPATH.'/third_party/OAuth.php');

/**
 * Twitter OAuth class
 */
class TwitterOAuth
{
	/* Contains the last HTTP status code returned. */
	public $http_code;
	/* Contains the last API call. */
	public $url;
	/* Set up the API root URL. */
	public $host = "https://api.twitter.com/1.1/";
	/* Set timeout default. */
	public $timeout = 30;
	/* Set connect timeout. */
	public $connecttimeout = 30; 
	/* Verify SSL Cert. */
	public $ssl_verifypeer = FALSE;
	/* Response format. */
	public $format = 'json';
	/* Decode returned json data. */
	public $decode_json = TRUE;
	/* Contains the last HTTP headers returned. */
	public $http_info;
	/* Set the useragnet. */
	public $useragent = 'TwitterOAuth for CodeIgniter';
	/* Immediately retry the API call if the response was not successful. */
	//public $retry = TRUE;

    private $token;

	function __construct()
	{
		// Do nothing	
	}
	
	/**
	 * Set API URLS
	 */
	function accessTokenURL()	{ return 'https://api.twitter.com/oauth/access_token';  }
	function authenticateURL()  { return 'https://api.twitter.com/oauth/authenticate';  }
	function authorizeURL()		{ return 'https://api.twitter.com/oauth/authorize';     }
	function requestTokenURL()  { return 'https://api.twitter.com/oauth/request_token'; }

	/**
	 * Debug helpers
	 */
	function lastStatusCode() { return $this->http_status;   }
	function lastAPICall()    { return $this->last_api_call; }

	/**
	 * construct TwitterOAuth object
	 */
	public function create($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL)
    {
		$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
		if (! empty($oauth_token) && ! empty($oauth_token_secret))
        {
			$this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
		} else {
			$this->token = NULL;
		}
		return $this;
	}

    /**
     * Get a request_token from Twitter
     *
     * @param $oauth_callback
     * @return array    key/value array containing oauth_token and oauth_token_secret
     */
    public function getRequestToken($oauth_callback)
    {
		$parameters = [];
		$parameters['oauth_callback'] = $oauth_callback;
		$request = $this->oAuthRequest($this->requestTokenURL(), 'POST', $parameters);
		$token = OAuthUtil::parse_parameters($request);
		$this->token = New OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);

		return $token;
	}

    /**
     * Get the authorize URL
     *
     * @param $token
     * @param bool $sign_in_with_twitter
     * @return string
     */
    public function getAuthorizeURL($token, $sign_in_with_twitter = TRUE)
    {
		if (is_array($token))  $token = $token['oauth_token'];

		if (empty($sign_in_with_twitter))
        {
			return "{$this->authorizeURL()}?oauth_token={$token}";
		} else {
			 return "{$this->authenticateURL()}?oauth_token={$token}";
		}
	}

    /**
     * Exchange request token and secret for an access token and
     * secret, to sign API calls.
     *
     * @param $oauth_verifier
     *
     * @return array(
     * "oauth_token"         => "the-access-token",
     *	"oauth_token_secret" => "the-access-secret",
     *	"user_id"            => "9436992",
     *	"screen_name"        => "abraham"
     * )
     */
    public function getAccessToken($oauth_verifier)
    {
		$parameters = array();
		$parameters['oauth_verifier'] = $oauth_verifier;
		$request = $this->oAuthRequest($this->accessTokenURL(), 'GET', $parameters);
		$token = OAuthUtil::parse_parameters($request);
		$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}

    /**
     * One time exchange of username and password for access token and secret.
     *
     * @param $username
     * @param $password
     *
     * @return array(
     * "oauth_token"         => "the-access-token",
     *	"oauth_token_secret" => "the-access-secret",
     *	"user_id"            => "9436992",
     *	"screen_name"        => "abraham",
     *	"x_auth_expires"     => "0"
     * )
     */
    public function getXAuthToken($username, $password)
    {
    	$parameters = [
		    'x_auth_username'   => $username,
		    'x_auth_password'   => $password,
		    'x_auth_mode'       => 'client_auth',
        ];

		$request = $this->oAuthRequest($this->accessTokenURL(), 'POST', $parameters);
		$token = OAuthUtil::parse_parameters($request);
		$this->token = New OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}

    /**
     * GET wrapper for oAuthRequest.
     *
     * @param $url
     * @param array $parameters
     * @return API|mixed
     */
    public function get($url, array $parameters = [])
    {
		$response = $this->oAuthRequest($url, 'GET', $parameters);
		return ('json' === strtolower($this->format) && $this->decode_json)
            ? json_decode($response)
		    : $response;
	}
	
    /**
     * POST wrapper for oAuthRequest.
     *
     * @param $url
     * @param array $parameters
     * @return API|mixed
     */
    public function post($url, array $parameters = [])
    {
		$response = $this->oAuthRequest($url, 'POST', $parameters);
        return ('json' === strtolower($this->format) && $this->decode_json)
            ? json_decode($response)
            : $response;
	}

    /**
     * DELETE wrapper for oAuthRequest.
     *
     * @param $url
     * @param array $parameters
     * @return API|mixed
     */
    public function delete($url, array $parameters = [])
    {
		$response = $this->oAuthRequest($url, 'DELETE', $parameters);
        return ('json' === strtolower($this->format) && $this->decode_json)
            ? json_decode($response)
            : $response;
	}

    /**
     * Format and sign an OAuth / API request
     *
     * @param $url
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function oAuthRequest($url, $method, $parameters)
    {
		if (0 !== strrpos($url, 'https://') && 0 !== strrpos($url, 'http://'))
			$url = "{$this->host}{$url}.{$this->format}";

		$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
		$request->sign_request($this->sha1_method, $this->consumer, $this->token);

        return ('get' === strtolower($method))
            ? $this->http($request->to_url(), 'GET')
            : $this->http($request->get_normalized_http_url(), $method, $request->to_postdata());
	}

    /**
     * Make an HTTP request
     *
     * @param $url
     * @param $method
     * @param null $data
     * @return mixed
     * @throws HttpException
     */
    public function http($url, $method, $data = NULL)
    {
		$this->http_info = [];

        $params = [];
        switch ($method)
        {
            case 'POST':
                $params = (! empty($data))
                    ? [ CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($data) ]
                    : [ CURLOPT_POST => 1 ];
            break;

            case 'DELETE':
                $params - [ CURLOPT_CUSTOMREQUEST => 'DELETE' ];
                if (! empty($data)) $url = "{$url}?{$data}";
            break;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_USERAGENT       => $this->useragent,
            CURLOPT_CONNECTTIMEOUT  => $this->connecttimeout,
            CURLOPT_SSL_VERIFYPEER  => $this->ssl_verifypeer,
            CURLOPT_TIMEOUT         => $this->timeout,
            CURLOPT_HEADERFUNCTION  => [$this, 'getHeader'],
            CURLOPT_HTTPHEADER      => ['Expect:'],
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_HEADER          => 0,
        ] + $params);

        if (FALSE === ($response = curl_exec($ch)))
            Throw New HttpException("Error with {$method} $url with error:\n", print_r(curl_error($ch), 1));

            $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->http_info = array_merge($this->http_info, curl_getinfo($ch));
            curl_close ($ch);

    		$this->url = $url;

		return $response;
	}

    /**
     * Get the header info to store.
     *
     * @param $header
     * @return int
     */
    public function getHeader($header)
    {
		$i = strpos($header, ':');

        if (! empty($i))
        {
			$key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
			$value = trim(substr($header, $i + 2));
			$this->http_header[$key] = $value;
		}
		return strlen($header);
	}
}