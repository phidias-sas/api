<?php
namespace Phidias\Api;

use Phidias\Api\Server\Module;

class Server
{
    private static $modules = [];

    public static function import($moduleFolder)
    {
        $path = realpath($moduleFolder);

        if (!$path) {
            trigger_error("cannot load: '$moduleFolder' is not a valid folder", E_USER_ERROR);
        }

        self::$modules[] = "/".trim($path, "/")."/";
    }

    public static function execute()
    {
        self::install();
    }

    public static function run()
    {
        self::install();
    }

    private static function install()
    {
        Module::install(self::$modules);
    }

    public static function onInitialize()
    {
    }

    public static function onNotFound()
    {
    }

    public static function onMethodNotImplemented()
    {
    }

    public static function resource()
    {
    }

    public static function find()
    {
    }

}


$index = realpath(".")."/index.php";

if (! is_file($index)) {
    echo "'$index' not found! Remember: you must run this script from the same path as your index.php\n";
    exit;
}

include $index;