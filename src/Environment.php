<?php
namespace Phidias\Api;

class Environment
{
    private static $adapter;

    private static function getAdapter()
    {
        if (self::$adapter == null) {

            switch (php_sapi_name()) {

                case "cli":
                    self::$adapter = new Environment\Cli;
                break;

                default:
                    self::$adapter = new Environment\Apache;
                break;

            }

        }

        return self::$adapter;
    }

    public static function getServerRequest()
    {
        return self::getAdapter()->getServerRequest();
    }

    public static function sendResponse(Http\Response $response)
    {
        return self::getAdapter()->sendResponse($response);
    }
}