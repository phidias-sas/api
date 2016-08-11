<?php
namespace Phidias\Api;

use Phidias\Api\Server\Module;

class Server
{
    public static function import($moduleFolder)
    {
        Module::load($moduleFolder);
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
        Module::install();
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