<?php
namespace Phidias\Api\Environment;

use Phidias\Api\Http\ServerRequest;
use Phidias\Api\Http\Uri;
use Phidias\Api\Http\Response;

class Cli implements EnvironmentInterface
{
    public static function getServerRequest()
    {
        global $argv;

        $url         = null;
        $queryParams = [];

        if (isset($argv[1])) {
            $parts = explode("?", $argv[1], 2);
            $url = $parts[0];
            if (isset($parts[1])) {
                parse_str($parts[1], $queryParams);
            }
        } else {
            $url = "/";
        }

        $request = (new ServerRequest)
            ->withMethod("get")
            ->withUri((new Uri())
                ->withHost("127.0.0.1")
                ->withPath($url)
            )
            ->withQueryParams($queryParams);

        foreach (self::getHeaders() as $headerName => $headerValue) {
            $request = $request->withHeader($headerName, explode(",", $headerValue));
        }


        return $request;
    }

    public static function sendResponse(Response $response)
    {
        $statusCode      = $response->getStatusCode();
        $reasonPhrase    = $response->getReasonPhrase();
        $protocolVersion = $response->getProtocolVersion();

        echo("HTTP/{$protocolVersion} {$statusCode} {$reasonPhrase}" . PHP_EOL);

        foreach ($response->getHeaders() as $headerName => $headerValues) {
            echo($headerName . ": " . implode(", ", $headerValues) . PHP_EOL);
        }

        echo (string)$response->getBody();
    }

    private static function getHeaders()
    {
        global $argv;
        $headers = [];

        foreach ($argv as $argument) {
            $parts = explode(":", $argument);
            if (count($parts) == 2) {
                $headers[$parts[0]] = $parts[1];
            }
        }
        return $headers;
    }    
}