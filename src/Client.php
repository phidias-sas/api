<?php
namespace Phidias\Api;

/*
Phidias API async client

This class is intended to be used inside you application logic, to make
asynchronous http requests to your Phidias Server's resources.

use Phidias\Api\Client as Client;

Client::post("/some/resource", [
    "message" => "hello!"
]);

Client::put("/threads/all/deliver");



*/

class Client
{
    public static function run($method, $url, $postdata = null)
    {
        $method = strtoupper($method);
        $url    = trim($url, '/');
        $host   = $_SERVER["HTTP_HOST"];
        $fullUrl = $_SERVER["REQUEST_SCHEME"] . "://" . $host . $_SERVER["CONTEXT_PREFIX"] . $url;

        $headers = [];
        $headers["Host"] = $host;
        if ($postdata) {
            $headers["Content-Type"] = 'application/json';
        }

        $command = 'curl -k -X '.$method;
        foreach ($headers as $headerName => $headerValue) {
            $command .= " -H \"$headerName: $headerValue\"";
        }

        if ($postdata) {
            $command .= " -d ".escapeshellarg(json_encode($postdata));
        }

        $command .= ' '.$fullUrl;

        self::execInBackground($command);
    }

    private static function execInBackground($cmd)
    {
        if (substr(php_uname(), 0, 7) == "Windows"){
            pclose(popen("start /B ". $cmd, "r"));
        } else {
            exec($cmd . " > /dev/null &");
        }
    }

    public static function get($url)
    {
        return self::run("GET", $url, $postdata);
    }

    public static function post($url, $postdata = null)
    {
        return self::run("POST", $url, $postdata);
    }

    public static function put($url, $postdata = null)
    {
        return self::run("PUT", $url, $postdata);
    }

    public static function delete($url, $postdata = null)
    {
        return self::run("DELETE", $url, $postdata);
    }
}
