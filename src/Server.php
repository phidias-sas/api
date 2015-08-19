<?php
namespace Phidias\Api;

use Phidias\Api\Server\Instance as ServerInstance;
use Phidias\Api\Http\ServerRequest;

class Server
{
    private static $instance;

    private static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new ServerInstance;
        }

        return self::$instance;
    }

    public static function resource($path, Resource $resource = null)
    {
        return self::getInstance()->resource($path, $resource);
    }

    public static function find($url)
    {
        return self::getInstance()->find($url);
    }

    public static function execute(ServerRequest $request)
    {
        return self::getInstance()->execute($request);
    }

    public static function run()
    {
        return self::getInstance()->run();
    }

    public static function import($path)
    {
        return self::getInstance()->import($path);
    }

    public static function onInitialize($callback)
    {
        return self::getInstance()->onInitialize($callback);
    }

    public static function accessControl()
    {
        return self::getInstance()->accessControl;
    }

}