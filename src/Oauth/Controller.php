<?php
namespace Phidias\Api\Oauth;

use Phidias\Utilities\Configuration;

class Controller
{
    /**
     * Authentication function
     * Validates and parses incoming token, and returns its payload
     *
     * To be used as a dispatcher authentication, like this:
     *
     * // Authenticate token in every request
     * Server::resource("*", [
     *     "any" => [
     *         "authentication" => "Phidias\Api\Oauth\Controller::authenticate({request})"
     *     ]
     * ]);
     *
     */
    public static function authenticate($request)
    {
        if ($request->hasHeader("authorization")) {
            list($authorizationMethod, $authorizationCredentials) = explode(" ", $request->getHeader("authorization")[0], 2);
            switch (strtolower($authorizationMethod)) {
                case "bearer":
                    Token::load( trim($authorizationCredentials) );
                    return Token::getPayload();
                break;

                case "basic":
                break;

                default:
                    throw new Exception\InvalidToken("unrecognized token format");
                break;
            }
        }
    }

    /**
     * POST requests to /oauth/token require the user to provide
     * username and password.  Custom validation functions may be defined
     * to validate this credentials, like this:
     *
     * Phidias\Api\Oauth\Controller::addCredentialsValidator(function($username, $password) {
     *      //voo doo
     *      return $payload;
     * })
     *
     * It must return the data to be used as the token payload, or throw and exception on failure
     */

    private static $credentialValidators;

    public static function addCredentialsValidator(Callable $callback)
    {
        if (self::$credentialValidators === null) {
            self::$credentialValidators = [];
        }

        self::$credentialValidators[] = $callback;
    }


    private static $emailValidators;

    public static function addEmailValidator(Callable $callback)
    {
        if (self::$emailValidators === null) {
            self::$emailValidators = [];
        }

        self::$emailValidators[] = $callback;
    }



    /**
     * Dispatch POST /oauth/token
     */
    public function token($request, $input)
    {
        switch ($input->grant_type) {

            case "client_credentials":
                $token = self::getTokenFromClientCredentials($request);
            break;

            // 2DO: implement this (!!!)
            case "authorization_code":
               throw new Exception\InvalidRequest("grant type not supported");
            break;

            default:
                throw new Exception\InvalidRequest("unknown grant type");
            break;

        }

        return $token;
    }

    /**
     * Dispatch POST /oauth/authorization
     */
    public function authorization($incoming)
    {
        //See http://tools.ietf.org/html/rfc6749#section-4.1.1
        $responseType = $incoming["response_type"]; //REQUIRED
        $clientId     = $incoming["client_id"];     //REQUIRED
        $redirectUri  = $incoming["redirect_uri"];  //OPTIONAL
        $scope        = $incoming["scope"];         //OPTIONAL
        $state        = $incoming["state"];         //OPTIONAL


        // 2DO: implement (!!!)
    }

    public function google($input)
    {
        if (!isset($input->code)) {
            throw new Exception\InvalidRequest("no code specified");
        }

        return self::getTokenFromGoogleAuthorizationCode($input->code);
    }

    public function office($input)
    {
        if (!isset($input->code)) {
            throw new Exception\InvalidRequest("no email specified");
        }

        return self::getTokenFromOfficeAuthorizationCode($input->code);
    }

    private static function getTokenFromOfficeAuthorizationCode($code)
    {
        $payload = self::validateEmail($code);

        return new Token("bearer", $payload);
    }

    private static function getTokenFromGoogleAuthorizationCode($code)
    {
        $userInfoUrl = "https://www.googleapis.com/oauth2/v4/token";
        $parameters = [
            "code"          => $code,
            "client_id"     => Configuration::get("phidias.oauth.google.client_id"),
            "client_secret" => Configuration::get("phidias.oauth.google.client_secret"),
            "redirect_uri"  => Configuration::get("phidias.oauth.google.redirect_uri"),
            "grant_type"    => "authorization_code"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch,CURLOPT_POST, count($parameters));
        curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($parameters));

        $response = curl_exec($ch);
        curl_close($ch);

        $responseData = json_decode($response);

        if (!isset($responseData->id_token)) {
            throw new Exception\InvalidRequest("google says: ".json_encode($responseData));
        }

        // Quick and dirty extraction of the token payload (following JWT specification)
        $tokenData = json_decode(base64_decode(explode(".", $responseData->id_token)[1]));

        if (!isset($tokenData->email)) {
            throw new Exception\InvalidRequest("could not obtain email from google token");
        }

        $payload = self::validateEmail($tokenData->email);

        return new Token("bearer", $payload);
    }


    private static function getTokenFromClientCredentials($request)
    {
        if (!$request->hasHeader("authorization")) {
            throw new Exception\InvalidRequest("no authorization header set");
        }

        list($authorizationMethod, $authorizationCredentials) = explode(" ", $request->getHeader("authorization")[0]);

        if (strtolower($authorizationMethod) != "basic") {
            throw new Exception\InvalidRequest("authorization header must contain basic header credentials");
        }

        $parts = explode(":", utf8_encode(base64_decode(trim($authorizationCredentials))));

        if (count($parts) != 2) {
            throw new Exception\InvalidRequest("malformed credentials header");
        }

        list($username, $password) = $parts;

        $payload = self::validateClientCredentials($username, $password);

        return new Token("bearer", $payload);
    }


    private static function validateClientCredentials($username, $password)
    {
        if (is_array(self::$credentialValidators)) {
            foreach (self::$credentialValidators as $validator) {

                $payload = call_user_func($validator, $username, $password);

                if ($payload !== false) {
                    return $payload;
                }
            }
        }

        throw new Exception\InvalidCredentials;
    }

    private static function validateEmail($email)
    {
        if (is_array(self::$emailValidators)) {
            foreach (self::$emailValidators as $validator) {

                $payload = call_user_func($validator, $email);

                if ($payload !== false) {
                    return $payload;
                }
            }
        }

        throw new Exception\InvalidCredentials;
    }

    public function apple($input)
    {
        if (!isset($input->email)) {
            throw new Exception\InvalidRequest("no email specified");
        }

        return self::getTokenFromAppleAuthorizationCode($input->email, $input->code);
    }

    private static function getTokenFromAppleAuthorizationCode($email, $code)
    {
        $payload = self::validateEmail($email);

        return new Token("bearer", $payload);
    }

}
