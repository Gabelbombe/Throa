<?php
/**
 * Generic exception class
 */
/*
if (! class_exists('OAuthException'))
{
    Class OAuthException Extends Exception { }
}
*/
Class OAuthConsumer
{
    public $key;
    public $secret;

    /**
    * @param $key
    * @param $secret
    * @param null $callbackUrl
    */
    public function __construct($key, $secret, $callbackUrl = null)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->callbackUrl = $callbackUrl;
    }

    /**
    * Non-Magic
    *
    * @return string
    */
    public function __toString()
    {
        return "OAuthConsumer[key=$this->key,secret=$this->secret]";
    }
}

Class OAuthToken
{
    // access tokens and request tokens
    public $key;
    public $secret;

    /**
    * @param $key
    * @param $secret
    */
    public function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
    * Generates the basic string serialization of a token that a server
    * would respond to request_token and access_token calls with
    *
    * @return string
    */
    public function toString()
    {
        return "oauth_token=" .
               OAuthUtil::urlEncodeRFC3986($this->key) .
               "&oauth_token_secret=" .
               OAuthUtil::urlEncodeRFC3986($this->secret);
    }

    /**
    * Non-Magic
    *
    * @return string
    */
    public function __toString()
    {
        return $this->toString();
    }
}

/**
 * A class for implementing a Signature Method
 * See section 9 ("Signing Requests") in the spec
 */
Abstract Class OAuthSignatureMethod 
{
   /**
    * Needs to return the name of the Signature Method (ie HMAC-SHA1)
    * @return string
    */
    abstract public function getName();

   /**
    * Build up the signature
    * NOTE: The output of this function MUST NOT be urlencoded.
    * the encoding is handled in OAuthRequest when the final
    * request is serialized
    *
    * @param OAuthRequest $request
    * @param OAuthConsumer $consumer
    * @param OAuthToken $token
    * @return mixed
    */
    abstract public function buildSignature($request, $consumer, $token);

   /**
    * Verifies that a given signature is correct
    * @param OAuthRequest $request
    * @param OAuthConsumer $consumer
    * @param OAuthToken $token
    * @param string $signature
    * @return bool
    */
    public function checkSignature($request, $consumer, $token, $signature)
    {
        $built = $this->buildSignature($request, $consumer, $token);

        return (
            $built == $signature
        );
    }
}

/**
 * The HMAC-SHA1 signature method uses the HMAC-SHA1 signature algorithm as defined in [RFC2104] 
 * where the Signature Base String is the text and the key is the concatenated values (each first 
 * encoded per Parameter Encoding) of the Consumer Secret and Token Secret, separated by an '&' 
 * character (ASCII code 38) even if empty.
 *   - Chapter 9.2 ("HMAC-SHA1")
 */
Class OAuthSignatureMethod_HMAC_SHA1 Extends OAuthSignatureMethod 
{
    /**
    * @return string
    */
    public function getName()
    {
        return "HMAC-SHA1";
    }

    /**
    * @param OAuthRequest $request
    * @param OAuthConsumer $consumer
    * @param OAuthToken $token
    * @return mixed|string
    */
    public function buildSignature($request, $consumer, $token) 
    {
        $baseString = $request->getSignatureBaseString();
        $request->baseString = $baseString;
        
        $keyParts = [
            $consumer->secret,
            ($token) ? $token->secret : ""
        ];
        
        $key = implode('&', OAuthUtil::urlEncodeRFC3986($keyParts));

        return base64_encode(hash_hmac('sha1', $baseString, $key, true));
    }
}

/**
 * The PLAINTEXT method does not provide any security protection and SHOULD only be used 
 * over a secure channel such as HTTPS. It does not use the Signature Base String.
 *   - Chapter 9.4 ("PLAINTEXT")
 */
Class OAuthSignatureMethod_PLAINTEXT Extends OAuthSignatureMethod
{
    /**
    * @return string
    */
    public function getName()
    {
        return "PLAINTEXT";
    }

   /**
    * oauth_signature is set to the concatenated encoded values of the Consumer Secret and
    * Token Secret, separated by a '&' character (ASCII code 38), even if either secret is
    * empty. The result MUST be encoded again.
    *   - Chapter 9.4.1 ("Generating Signatures")
    *
    * Please note that the second encoding MUST NOT happen in the SignatureMethod, as
    * OAuthRequest handles this!
    */
    public function buildSignature($request, $consumer, $token)
    {
        $keyParts = [
            $consumer->secret,
            ($token) ? $token->secret : ""
        ];

        $key = implode('&', OAuthUtil::urlEncodeRFC3986($keyParts));
        $request->baseString = $key;

        return $key;
    }
}

/**
 * The RSA-SHA1 signature method uses the RSASSA-PKCS1-v1_5 signature algorithm as defined in 
 * [RFC3447] section 8.2 (more simply known as PKCS#1), using SHA-1 as the hash function for 
 * EMSA-PKCS1-v1_5. It is assumed that the Consumer has provided its RSA public key in a 
 * verified way to the Service Provider, in a manner which is beyond the scope of this 
 * specification.
 *   - Chapter 9.3 ("RSA-SHA1")
 */
Abstract Class OAuthSignatureMethod_RSA_SHA1 Extends OAuthSignatureMethod
{
    /**
    * @return string
    */
    public function getName()
    {
        return "RSA-SHA1";
    }

    // Up to the SP to implement this lookup of keys. Possible ideas are:
    // (1) do a lookup in a table of trusted certs keyed off of consumer
    // (2) fetch via http using a url provided by the requester
    // (3) some sort of specific discovery code based on request
    //
    // Either way should return a string representation of the certificate
    protected abstract function fetchPublicCert(&$request);

    // Up to the SP to implement this lookup of keys. Possible ideas are:
    // (1) do a lookup in a table of trusted certs keyed off of consumer
    //
    // Either way should return a string representation of the certificate
    protected abstract function fetchPrivateCert(&$request);
    
    public function buildSignature($request, $consumer, $token) 
    {
        $baseString = $request->getSignatureBaseString();
        $request->baseString = $baseString;
        
        // Fetch the private key cert based on the request
        $cert = $this->fetchPrivateCert($request);
        
        // Pull the private key ID from the certificate
        $prvKeyId = openssl_get_privatekey($cert);
        
        // Sign using the key
        // does not require variable assignment unless debugging
        openssl_sign($baseString, $signature, $prvKeyId);
        
        // Release the key resource
        openssl_free_key($prvKeyId);
        
        return base64_encode($signature);
    }
    
    public function checkSignature($request, $consumer, $token, $signature) 
    {
        $decoded_sig = base64_decode($signature);
        
        $baseString = $request->getSignatureBaseString();
        
        // Fetch the public key cert based on the request
        $cert = $this->fetchPublicCert($request);
        
        // Pull the public key ID from the certificate
        $pubKeyId = openssl_get_publickey($cert);
        
        // Check the computed signature against the one passed in the query
        $ok = openssl_verify($baseString, $decoded_sig, $pubKeyId);
        
        // Release the key resource
        openssl_free_key($pubKeyId);
        
        return (
            1 == $ok
        );
    }
}

Class OAuthRequest 
{
    private $parameters;
    private $httpMethod;
    private $httpUrl;

    // for debug purposes
    public $baseString;
    public static $version = '1.0';
    public static $POST_INPUT = 'php://input';

    /**
    * @param $httpMethod
    * @param $httpUrl
    * @param array $parameters
    */
    public function __construct($httpMethod, $httpUrl, array $parameters = [])
    {
        $parameters = array_merge( OAuthUtil::parseParameters(parse_url($httpUrl, PHP_URL_QUERY)), $parameters);
        $this->parameters = $parameters;
        $this->httpMethod = $httpMethod;
        $this->httpUrl = $httpUrl;
    }
    
    
    /**
    * Attempt to build up a request from what was passed to the server
    * 
    * @param null $httpMethod
    * @param null $httpUrl
    * @param null $parameters
    * @return OAuthRequest
    */
    public static function fromRequest($httpMethod = null, $httpUrl = null, $parameters = null)
    {
        $scheme = (! isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on")
            ? 'http'
            : 'https';

        $httpUrl = (null !== $httpUrl)
            ? "{$scheme}://{$_SERVER['HTTP_HOST']}:{$_SERVER['SERVER_PORT']}{$_SERVER['REQUEST_URI']}"
            : $httpUrl;

        $httpMethod = (null !== $httpMethod)
            ? $_SERVER['REQUEST_METHOD']
            : $httpMethod;

        // We weren't handed any parameters, so let's find the ones relevant to
        // this request.
        // If you run XML-RPC or similar you should use this to provide your own
        // parsed parameter-list
        if (! $parameters) 
        {
            // Find request headers
            $requestHeaders = OAuthUtil::getHeaders();
            
            // Parse the query-string to find GET parameters
            $parameters = OAuthUtil::parseParameters($_SERVER['QUERY_STRING']);
            
            // It's a POST request of the proper content-type, so parse POST
            // parameters and add those overriding any duplicates from GET
            if (isset($requestHeaders["Content-Type"]) && strstr($requestHeaders["Content-Type"], 'application/x-www-form-urlencoded')
            && 'post' === strtolower($httpMethod))

                $parameters = array_merge($parameters, OAuthUtil::parseParameters(file_get_contents(self::$POST_INPUT)));
            
            // We have a Authorization-header with OAuth data. Parse the header
            // and add those overriding any duplicates from GET or POST
            if (isset($requestHeaders['Authorization']) && substr($requestHeaders['Authorization'], 0, 6) == "OAuth ") 
            {
                $headerParameters = OAuthUtil::splitHeader($requestHeaders['Authorization']);
                $parameters = array_merge($parameters, $headerParameters);
            }

        }

        return New OAuthRequest($httpMethod, $httpUrl, $parameters);
    }
    
    /**
     * pretty much a helper function to set up the request
     */
    public static function fromConsumerAndToken($consumer, $token, $httpMethod, $httpUrl, $parameters = null)
    {
        $parameters = (isset($parameters) && ! empty($parameters))
            ? $parameters
            : [];

        $defaults = [
            "oauth_version"      => OAuthRequest::$version,
            "oauth_nonce"        => OAuthRequest::generateNonce(),
            "oauth_timestamp"    => OAuthRequest::generateTimestamp(),
            "oauth_consumer_key" => $consumer->key
        ];
    
            if ($token) $defaults['oauth_token'] = $token->key;
        
        $parameters = array_merge($defaults, $parameters);
        
        return New OAuthRequest($httpMethod, $httpUrl, $parameters);
    }
    
    public function setParameter($name, $value, $allowDupes = true) 
    {
        if ($allowDupes && isset($this->parameters[$name]))
        {
          // We have already added parameter(s) with this name, so add to the list
          if (is_scalar($this->parameters[$name]))
          {
            // This is the first duplicate, so transform scalar (string)
            // into an array so we can add the duplicates
            $this->parameters[$name] = array($this->parameters[$name]);
          }

          $this->parameters[$name][] = $value;
        } else {
          $this->parameters[$name] = $value;
        }
    }
    
    public function getParameter($name) 
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }
    
    public function getParameters() 
    {
        return $this->parameters;
    }
    
    public function unsetParameters($name) 
    {
        unset($this->parameters[$name]);
    }
    
    /**
    * The request parameters, sorted and concatenated into a normalized string.
    * @return string
    */
    public function getSignableParameters() 
    {
        // Grab all parameters
        $params = $this->parameters;
        
        // Remove oauth_signature if present
        // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
        if (isset($params['oauth_signature'])) {
          unset($params['oauth_signature']);
        }
        
        return OAuthUtil::buildHttpQuery($params);
    }
    
    /**
    * Returns the base string of this request
    *
    * The base string defined as the method, the url
    * and the parameters (normalized), each urlencoded
    * and the concated with &.
    */
    public function getSignatureBaseString() 
    {
        $parts = [
            $this->getNormalizedHttpMethod(),
            $this->getNormalizedHttpUrl(),
            $this->getSignableParameters(),
        ];
    
        $parts = OAuthUtil::urlEncodeRFC3986($parts);
    
        return implode('&', $parts);
    }
    
    /**
    * just uppercases the http method
    */
    public function getNormalizedHttpMethod() 
    {
        return strtoupper($this->httpMethod);
    }
    
    /**
    * parses the url and rebuilds it to be
    * scheme://host/path
    */
    public function getNormalizedHttpUrl() 
    {
        $port   = // variable declaration so the IDE stops snivelling
        $scheme =
        $host   =
        $path   = false;

        $parts = parse_url($this->httpUrl);

        foreach(['port', 'scheme', 'host', 'path'] AS $variable)
        {
            $$variable =  (isset($parts[$variable]) && ! empty($parts[$variable]))
                ? $variable
                : false;
        }

        // watch this guy, he's an asshole
        if (isset($port) && ! empty($port))
            $port = ('https' === strtolower($scheme))
                ? 443
                : 80;

        if (('https' === strtolower($scheme) && (443||80) != (int) $port))
            $host = "$host:$port";

        return "$scheme://$host$path";
    }
    
    /**
    * builds a url usable for a GET request
    */
    public function toUrl() 
    {
        $data = $this->toPostData(); // min versions hate the above =P

        return (! empty($data))
            ? "{$this->toPostData()}?{$this->getNormalizedHttpUrl()}"
            : $this->getNormalizedHttpUrl();

    }
    
    /**
    * builds the data one would send in a POST request
    *
    * @return string
    */
    public function toPostData()
    {
        return OAuthUtil::buildHttpQuery($this->parameters);
    }
    
    /**
     * builds the Authorization: header
     */
    public function toHeader($realm = null)
    {
        $first = true;

        if($realm)
        {
            $out = 'Authorization: OAuth realm="' . OAuthUtil::urlEncodeRFC3986($realm) . '"';
            $first = false;
        } else {
            $out = 'Authorization: OAuth';
        }

        foreach ($this->parameters AS $k => $v)
        {
            if (substr($k, 0, 5) != "oauth") continue;
            if (is_array($v)) Throw New \OAuthException('Arrays not supported in headers');

            $out .= ($first)
              ? ' '
              : ',';

            $out .= "{OAuthUtil::urlEncodeRFC3986($k)}='{OAuthUtil::urlEncodeRFC3986($v)}'";
            $first = false;
        }

        return $out;
    }
    
    public function __toString() 
    {
        return $this->toUrl();
    }
    
    public function signRequest($signatureMethod, $consumer, $token) 
    {
        $this->setParameter(
            'oauth_signature_method',
            (
                method_exists($signatureMethod, 'getName')
                    ? $signatureMethod->getName()
                    : false
            ),
            false);

        $signature = $this->buildSignature($signatureMethod, $consumer, $token);

        $this->setParameter("oauth_signature", $signature, false);
    }
    
    public function buildSignature($signatureMethod, $consumer, $token) 
    {
        // oikology, check class before __call otherwise hurt imminent.....
        return (method_exists($signatureMethod, 'buildSignature'))
            ? $signatureMethod->buildSignature($this, $consumer, $token)
            : false;
    }
    
    /**
     * util function: current timestamp
     */
    private static function generateTimestamp() 
    {
        return time();
    }
    
    /**
     * util function: current nonce
     */
    private static function generateNonce()
    {
        return md5(microtime() . mt_rand()); // md5s look nicer than numbers
    }
}

Class OAuthServer 
{
    protected $timestampThreshold = 300;    // in seconds, five minutes
    protected $version = '1.0';             // hi blaine <--- hahaha wtf?
    protected $signatureMethods = [];
    
    protected $dataStore;
    
    public function __construct($dataStore)
    {
        // Guarantee we are a Data Storage instance
        $this->dataStore = (! is_a($dataStore, 'OAuthDataStore'))
            ? New OAuthDataStore()
            : $dataStore;
    }
    
    public function addSignatureMethod($signatureMethod)
    {
        $this->signatureMethods[(method_exists($signatureMethod, 'getName') ? $signatureMethod->getName() : false)] = $signatureMethod;
    }
    
    // high level functions
    
    /**
     * process a request_token request
     * returns the request token on success
     */
    public function fetchRequestToken(&$request) 
    {
        $this->getVersion($request);
        
        $consumer = $this->getConsumer($request);
        
        // no token required for the initial token request
        $token = null;
        
        $this->checkSignature($request, $consumer, $token);
        
        // Rev A change
        $callback = (method_exists($request, 'getParameter'))
            ? $request->getParameter('oauth_callback')
            : 'Are we really still playing this shit...?';

        return (method_exists($this->dataStore, 'newRequestToken'))
            ? $this->dataStore->newRequestToken($consumer, $callback)
            : 'A challenger appears!';
    }
    
    /**
     * process an access_token request
     * returns the access token on success
     */
    public function fetchAccessToken(&$request) 
    {
        $this->getVersion($request);
        
        $consumer = $this->getConsumer($request);
        
        // requires authorized request token
        $token = $this->getToken($request, $consumer, "request");
        
        $this->checkSignature($request, $consumer, $token);
        
        // Rev A change
        $verifier = (method_exists($request, 'getParameter'))
            ? $request->getParameter('oauth_callback')
            : 'Seriously, this isn\'t funny anymore... You could have made all of this legal with the fucking proxy patter, rea a fucking book dude....';

        return (method_exists($this->dataStore, 'newAccessToken'))
            ? $this->dataStore->newAccessToken($token, $consumer, $verifier)
            : 'A challenger appears!';
    }
    
    /**
     * verify an api call, checks all the parameters
     */
    public function verifyRequest(&$request)
    {
        $this->getVersion($request);
        $consumer = $this->getConsumer($request);
        $token = $this->getToken($request, $consumer, "access");
        $this->checkSignature($request, $consumer, $token);

        return [ $consumer, $token ];
    }
    
    // Internals from here
    /**
     * version 1
     */
    private function getVersion(&$request)
    {
        $version = (method_exists($request, 'getParameter'))
            ? $request->getParameter('oauth_version')
            : 'fuck hoes, get money';

        if (! $version)
        {
          // Service Providers MUST assume the protocol version to be 1.0 if this parameter is not present.
          // Chapter 7.0 ("Accessing Protected Ressources")
          $version = '1.0';
        }

        if ($version !== $this->version)
        {
          Throw New OAuthException("OAuth version '$version' not supported");
        }

        return $version;
    }
    
    /**
     * figure out the signature with some defaults
     */
    private function getSignatureMethod(&$request)
    {
        $signatureMethod = (method_exists($request, 'getParameter'))
            ? $request->getParameter('oath_signature_method')
            : 'fuck you fuck you fuck you this never even fucking existed you magic method peace of fucking shit...';

        if (! $signatureMethod)
        {
            // According to chapter 7 ("Accessing Protected Resources") the signature-method
            // parameter is required, and we can't just fallback to PLAINTEXT
            Throw New OAuthException('No signature method parameter. This parameter is required');
        }

        if (! in_array($signatureMethod, array_keys($this->signatureMethods)))
        {
            Throw New OAuthException("Signature method '{$signatureMethod}' not supported try one of the following: " .
                                     implode(", ", array_keys($this->signatureMethods))
          );
        }
        return $this->signatureMethods[$signatureMethod];
    }
    
    /**
     * try to find the consumer for the provided request's consumer key
     */
    private function getConsumer(&$request)
    {
        $consumerKey = (method_exists($request, 'getParameter'))
            ? $request->getParameter("oauth_consumer_key")
            : false;

        if (! $consumerKey) Throw New OAuthException("Invalid consumer key");


            $consumer = (method_exists($this->dataStore, 'lookupConsumer'))
                ? $this->dataStore->lookupConsumer($consumerKey)
                : false;

        if (! $consumer) Throw new OAuthException("Invalid consumer");

        return $consumer;
    }
    
    /**
     * try to find the token for the provided request's token key
     */
    private function getToken(&$request, $consumer, $tokenType = 'access') 
    {
        $tokenField = (method_exists($request, 'getParameter'))
            ? $request->getParameter('oauth_token')
            : 'Seriously, will you stop calling shit that DOES NOT FUCKING EXISTS?!?!?!';

        $token = $this->dataStore->lookupToken(
            $consumer, $tokenType, $tokenField
        );

        if (! $token) Throw New OAuthException("Invalid $tokenType token: $tokenField");

        return $token;
    }
    
    /**
     * all-in-one function to check the signature on a request
     * should guess the signature method appropriately
     */
    private function checkSignature(&$request, $consumer, $token) 
    {
        // this should probably be in a different method
        $timestamp = (method_exists($request, 'getParameter'))
            ? $request->getParameter('oauth_timestamp')
            : time(); // guess-timation?
 
        $nonce = (method_exists($request, 'getParameter'))
            ? $request->getParameter('oauth_nonce')
            : false;
        
        $this->checkTimestamp($timestamp);
        $this->checkNonce($consumer, $token, $nonce, $timestamp);
        
        $signatureMethod = $this->getSignatureMethod($request);
        
        $signature = (method_exists($request, 'gertParameter'))
            ? $request->getParameter('oauth_signature')
            : false;

        if (! method_exists($signatureMethod, 'checkSignature')
        ||  ! $signatureMethod->checkSignature($request, $consumer, $token, $signature))

            Throw New OAuthException('Invalid signature');
    }
    
    /**
     * check that the timestamp is new enough
     */
    private function checkTimestamp($timestamp)
    {
        if( ! $timestamp ) Throw New OAuthException('Missing timestamp parameter. The parameter is required');

        // verify that timestamp is recent(ish)
        $now = time();

        if (abs($now - $timestamp) > $this->timestampThreshold)

            Throw New OAuthException("Expired timestamp, yours {$timestamp}, ours {$now}");
    }
    
    /**
    * check that the nonce is not repeated
    */
    private function checkNonce($consumer, $token, $nonce, $timestamp) 
    {
        if( ! $nonce )
          Throw New OAuthException(
            'Missing nonce parameter. The parameter is required'
          );

            // verify that the nonce is unique(ish)
            $found = $this->dataStore->lookupNonce($consumer, $token, $nonce, $timestamp);

            if ($found) Throw New OAuthException("Nonce already used: $nonce");
    }
}
    
Class OAuthDataStore 
{
    public function lookupConsumer($consumerKey)
    {
        return true; // implement me
    }
    
    public function lookupToken($consumer, $tokenType, $token)
    {
        return true; // implement me
    }
    
    public function lookupNonce($consumer, $token, $nonce, $timestamp)
    {
        return true; // implement me
    }
    
    public function newRequestToken($consumer, $callback = null)
    {
        return true; // return a new token attached to this consumer
    }
    
    public function newAccessToken($token, $consumer, $verifier = null)
    {
        // return a new access token attached to this consumer
        // for the user associated with this token if the request token
        // is authorized
        // should also invalidate the request token
        return true;
    }
}

Class OAuthUtil
{
    public static function urlEncodeRFC3986($input)
    {
        if (is_array(input))    return array_map(array('OAuthUtil', 'urlEncodeRFC3986'), $input);
        if (is_scalar($input))  return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode($input)));

            return ''; // why return empty? makes no sense....
    }


    // This decode function isn't taking into consideration the above
    // modifications to the encoding process. However, this method doesn't
    // seem to be used anywhere so leaving it as is.
    public static function urlDecodeRFC3986($string) 
    {
        return urldecode($string);
    }

    // Utility function for turning the Authorization: header into
    // parameters, has to do some unescaping
    // Can filter out any non-oauth parameters if needed (default behaviour)
    public static function splitHeader($header, $onlyAllowOAuthParameters = true)
    {
        $offset = 0;
        $params = [];

        while (preg_match('/(([-_a-z]*)=("([^"]*)"|([^,]*)),?)/', $header, $matches, PREG_OFFSET_CAPTURE, $offset) > 0)
        {
            $match = $matches[0];
            $headerName = $matches[2][0];
            $headerContent = (isset($matches[5])) ? $matches[5][0] : $matches[4][0];

            if (preg_match('/^oauth_/', $headerName) || ! $onlyAllowOAuthParameters)
                $params[$headerName] = OAuthUtil::urlDecodeRFC3986($headerContent);
 
          $offset = $match[1] + strlen($match[0]);
        }

        if (isset($params['realm']))
            unset($params['realm']);

        return $params;
    }

    // helper to try to sort out headers for people who aren't running apache
    public static function getHeaders()
    {
        if (function_exists('apache_request_headers'))
        {
          // we need this to get the actual Authorization: header
          // because apache tends to tell us it doesn't exist
          $headers = apache_request_headers();

          // sanitize the output of apache_request_headers because
          // we always want the keys to be Cased-Like-This and arh()
          // returns the headers in the same case as they are in the
          // request
          $out = [];

          foreach( $headers AS $key => $value )
            $out[str_replace(' ', '-', ucwords(strtolower(str_replace("-", " ", $key))))] = $value;

        } else {
            // otherwise we don't have apache and are just going to have to hope
            // that $_SERVER actually contains what we need
            $out = [];
            if( isset($_SERVER['CONTENT_TYPE']) )
                $out['Content-Type'] = $_SERVER['CONTENT_TYPE'];

            if( isset($_ENV['CONTENT_TYPE']) )
                $out['Content-Type'] = $_ENV['CONTENT_TYPE'];

            foreach ($_SERVER as $key => $value)
            {
                if (substr($key, 0, 5) == "HTTP_")
                {
                  // this is chaos, basically it is just there to capitalize the first
                  // letter of every word that is not an initial HTTP and strip HTTP
                  // code from przemek
                  $out[str_replace(' ', '-', ucwords(strtolower(str_replace("_", " ", substr($key, 5)))))] = $value;
                }
            }
        }
        return $out;
    }

    // This function takes a input like a=b&a=c&d=e and returns the parsed
    // parameters like this
    // array('a' => array('b','c'), 'd' => 'e')
    public static function parseParameters( $input ) 
    {
        if (! isset($input) || !$input) return [];
    
        $pairs = explode('&', $input);
        
        $parsedParameters = [];
        foreach ($pairs AS $pair) 
        {
            $split = explode('=', $pair, 2);
            $parameter = OAuthUtil::urlDecodeRFC3986($split[0]);
            $value = isset($split[1]) ? OAuthUtil::urlDecodeRFC3986($split[1]) : '';
            
            if (isset($parsedParameters[$parameter])) 
            {
                // We have already recieved parameter(s) with this name, so add to the list
                // of parameters with this name
            
                if (is_scalar($parsedParameters[$parameter])) 
                {
                    // This is the first duplicate, so transform scalar (string) into an array
                    // so we can add the duplicates
                    $parsedParameters[$parameter] = [ $parsedParameters[$parameter] ];
                }
            
                $parsedParameters[$parameter][] = $value;
            } else {
                $parsedParameters[$parameter] = $value;
            }
        }
        return $parsedParameters;
    }

    public static function buildHttpQuery($params) 
    {
        if (!$params) return '';
        
        // Urlencode both keys and values
        $keys = OAuthUtil::urlEncodeRFC3986(array_keys($params));
        $values = OAuthUtil::urlEncodeRFC3986(array_values($params));
        $params = array_combine($keys, $values);
        
        // Parameters are sorted by name, using lexicographical byte value ordering.
        // Ref: Spec: 9.1.1 (1)
        uksort($params, 'strcmp');
        
        $pairs = [];
        foreach ($params as $parameter => $value) 
        {
            if (is_array($value)) 
            {
                // If two or more parameters share the same name, they are sorted by their value
                // Ref: Spec: 9.1.1 (1)
                natsort($value);

                foreach ($value as $duplicateValues)
                    $pairs[] = "{$parameter}={$duplicateValues}";

            } else {
                $pairs[] = "{$parameter}={$value}";
            }
        }

        // For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61)
        // Each name-value pair is separated by an '&' character (ASCII code 38)
        return implode('&', $pairs);
    }
}