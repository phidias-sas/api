<?php
namespace Phidias\Api\Server\Module;

class Autoloader
{
    private static $isRegistered = false;
    private static $paths        = [];

    public static function path($path)
    {
        self::register();
        self::$paths[] = $path;
    }

    private static function register()
    {
        if (self::$isRegistered) {
            return;
        }

        spl_autoload_register(['Phidias\Api\Server\Module\Autoloader', 'loadClass']);
        self::$isRegistered = true;
    }

    public static function loadClass($class)
    {
        foreach (self::$paths as $path) {
            $targetFile = "$path/".str_replace("\\", DIRECTORY_SEPARATOR, $class).".php";
            if (is_file($targetFile)) {
                include $targetFile;
                return;
            }
        }
    }

}