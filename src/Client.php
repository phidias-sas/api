<?php
namespace Phidias\Api;

/*
Phidias API async client

This class is intended to be used inside you application logic, to make
asynchronous http requests to your Phidias Server's resources.

use Phidias\Api\Client as ApiClient;

$myApi = new ApiClient();  // without constructor arguments it will use the current running server

$myApi->post("/some/resource", ["message" => "hello!"]);
$myApi->put("/threads/all/deliver");

*/

class Client
{
    private $hostName;
    private $requestScheme;
    private $contextPrefix;

    public function __construct($hostName = null, $requestScheme = null, $contextPrefix = null)
    {
        if (!$hostName) {
            $this->setHost($_SERVER["HTTP_HOST"], $_SERVER["REQUEST_SCHEME"], $_SERVER["CONTEXT_PREFIX"]);
            return;
        }

        $this->setHost($hostName, $requestScheme, $contextPrefix);
    }

    public function setHost($hostName, $requestScheme = "https", $contextPrefix = null)
    {
        // Look for embeded data in hostName (e.g. "https://some.host.name/prefix/")
        $parts = explode("://", $hostName);
        if (count($parts) == 2) {
            $requestScheme = $parts[0];
            $hostName      = $parts[1];

            $hostParts = explode("/", $hostName, 2);
            if (count($hostParts) == 2) {
                $hostName = $hostParts[0];
                $contextPrefix = "/" . trim($hostParts[1],"/");
            }
        }

        $this->hostName      = $hostName;
        $this->requestScheme = $requestScheme;
        $this->contextPrefix = $contextPrefix;
    }

    public function getBaseUrl()
    {
        // return trim("https://{$this->hostName}{$this->contextPrefix}/", "/");
        return trim("{$this->requestScheme}://{$this->hostName}{$this->contextPrefix}/", "/");
    }

    public function execute($method, $url, $postdata = null)
    {
        $method  = strtoupper($method);
        $fullUrl = $this->getBaseUrl() . "/" . trim($url, '/');

        $headers = [];
        $headers["Host"] = $this->hostName;
        if ($postdata) {
            $headers["Content-Type"] = 'application/json';
        }

        $command = 'curl -k -X '.$method;
        foreach ($headers as $headerName => $headerValue) {
            $command .= " -H \"$headerName: $headerValue\"";
        }

        if ($postdata) {
            $command .= " -d ".self::escapeCmdArg(json_encode($postdata));
        }

        $command .= ' '.$fullUrl;

        self::execInBackground($command);

        return $command;
    }

    private static function escapeCmdArg($value)
    {
        return str_replace('"', '\"', $value);
    }

    private static function execInBackground($cmd)
    {
        if (substr(php_uname(), 0, 7) == "Windows"){
            pclose(popen("start /B ". $cmd, "r"));
        } else {
            exec($cmd . " > /dev/null &");
        }
    }

    public function get($url)
    {
        return $this->execute("GET", $url);
    }

    public function post($url, $postdata = null)
    {
        return $this->execute("POST", $url, $postdata);
    }

    public function put($url, $postdata = null)
    {
        return $this->execute("PUT", $url, $postdata);
    }

    public function delete($url, $postdata = null)
    {
        return $this->execute("DELETE", $url, $postdata);
    }
}
