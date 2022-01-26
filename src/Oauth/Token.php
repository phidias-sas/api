<?php
namespace Phidias\Api\Oauth;

use Firebase\JWT\JWT;

class Token
{
    public $token_type;
    public $access_token;
    public $expires_in;
    public $scope;
    public $refresh_token;

    private static $payload;
    private static $secret;

    public static function setSecret($secret)
    {
        self::$secret = $secret;
    }

    public static function getPayload()
    {
        return self::$payload;
    }

    public static function load($token)
    {
        try {
            // self::$payload = JWT::decode($token, self::$secret, ["HS256"]);
            self::$payload = JWT::decode($token, 'sssshhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhh!', ["HS256"]);
        } catch (\Exception $e) {
            throw new Exception\InvalidToken;
        }
    }

    public function __construct($type, $payload)
    {
        $this->token_type      = $type;
        // $this->access_token    = JWT::encode($payload, self::$secret);
        $this->access_token    = JWT::encode($payload, 'sssshhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhh!');
        //$this->expires_in    = "???";
        //$this->scope         = "???";
        //$this->refresh_token = "???";
    }

}