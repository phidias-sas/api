<?php
namespace Phidias\Api\Server;

use Phidias\Utilities\Configuration;
use Phidias\Utilities\Debugger;

use Phidias\Api\Server\Module\Autoloader;
use Phidias\Api\Server\Module\Installer;

class Module
{
    //Default folder structure
    const DIR_INITIALIZATION = 'initialize';
    const DIR_SOURCES        = 'src';
    const DIR_CONFIGURATION  = 'configuration';
    const DIR_RESOURCES      = 'resources';
    const DIR_TEMPLATES      = 'templates';
    const DIR_TEMPORARY      = 'temporary';

    public static function load(Instance $server, $moduleFolder)
    {
        $path = realpath($moduleFolder);

        if (! $path) {
            trigger_error("cannot load: '$moduleFolder' is not a valid folder", E_USER_ERROR);
        }

        Debugger::startBlock("Loading module '$path'");

        $path = $path."/";

        if (is_file("$path/vendor/autoload.php")) {
            include "$path/vendor/autoload.php";
        } elseif (is_dir($path.self::DIR_SOURCES)) {
            Autoloader::path($path.self::DIR_SOURCES);
        }

        self::loadConfiguration($server, $path);
        self::loadResources($server, $path);

        $server->onInitialize(function() use ($server, $path) {
            self::runInitialization($server, $path);
        });

        Debugger::endBlock("Loading module '$path'");
    }

    public static function install($modules)
    {
        /* Step 1: Include all configuration */
        foreach ($modules as $path) {
            foreach (self::readAllFolder($path."/".self::DIR_CONFIGURATION) as $file) {
                Configuration::set(include $file);
            }
        }

        /* Step 2: Run all modules initialization */
        foreach ($modules as $path) {
            foreach (self::readAllFolder($path."/".self::DIR_INITIALIZATION) as $file) {
                include $file;
            }
        }

        /* Step 3: Run installation scripts */
        foreach ($modules as $path) {
            (new Installer($path))->install();
        }
    }


    private static function runInitialization($server, $path)
    {
        foreach (self::readAllFolder($path.self::DIR_INITIALIZATION) as $file) {
            include $file;
        }
    }

    private static function loadConfiguration($server, $path)
    {
        foreach (self::readAllFolder($path.self::DIR_CONFIGURATION) as $file) {
            Configuration::set(include $file);
        }

    }

    private static function loadResources($server, $path)
    {
        foreach (self::readAllFolder($path.self::DIR_RESOURCES) as $file) {
            $server->resource(include $file);
        }
    }


    private static function readAllFolder($folder, $subfoldersFirst = true, $rootFolder = null)
    {
        $files   = [];
        $folders = [];

        if ($rootFolder === null) {
            $rootFolder = $folder;
            $folder  = null;
        }

        if (!is_dir("$rootFolder/$folder")) {
            return [];
        }

        foreach (self::readFolder("$rootFolder/$folder") as $basename) {

            $item = $folder === null ? $basename : "$folder/$basename";

            if (is_file("$rootFolder/$item")) {
                $files[] = "$rootFolder/$item";
            } else {
                $folders[] = $item;
            }
        }

        $retval = [];

        if ($subfoldersFirst) {
            foreach ($folders as $subdir) {
                $retval = array_merge($retval, self::readAllFolder($subdir, $subfoldersFirst, $rootFolder));
            }
            $retval = array_merge($retval, $files);
        } else {
            $retval = $files;
            foreach ($folders as $subdir) {
                $retval = array_merge($retval, self::readAllFolder($subdir, $subfoldersFirst, $rootFolder));
            }
        }

        return $retval;
    }

    /**
     * Returns all files and folders contained in $folder
     * relative to $folder
     */
    private static function readFolder($folder)
    {
        if ($handle = opendir($folder)) {

            $entries = [];

            /* This is the correct way to loop over the folder. */
            while (false !== ($entry = readdir($handle))) {

                //ignore . and ..
                if ($entry === "." || $entry === "..") {
                    continue;
                }

                $entries[] = $entry;
            }

            closedir($handle);

            return $entries;
        }

        return null;
    }

}