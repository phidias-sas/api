<?php
namespace Phidias\Api\Server;

use Phidias\Utilities\Configuration;
use Phidias\Utilities\Debugger;
use Phidias\Api\Server;
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

    private static $loadedModules = [];

    public static function load($moduleFolder)
    {
        $path = realpath($moduleFolder);

        if (!$path) {
            trigger_error("cannot load: '$moduleFolder' is not a valid folder", E_USER_ERROR);
        }

        $path = rtrim($path, "/");
        self::loadConfiguration($path);
        self::$loadedModules[] = $path;
    }

    public static function initialize()
    {
        foreach (self::$loadedModules as $path) {
            self::runInitialization($path);
        }

        foreach (self::$loadedModules as $path) {
            self::loadResources($path);
        }
    }

    public static function getLoadedModules()
    {
        return self::$loadedModules;
    }


    private static function runInitialization($path)
    {
        foreach (self::getFileList($path."/".self::DIR_INITIALIZATION) as $file) {
            include $file;
        }
    }

    private static function loadConfiguration($path)
    {
        foreach (self::getFileList($path."/".self::DIR_CONFIGURATION) as $file) {
            Configuration::set(include $file);
        }
    }

    private static function loadResources($path)
    {
        foreach (self::getFileList($path."/".self::DIR_RESOURCES) as $file) {
            Server::resource(include $file);
        }
    }



    public static function install()
    {
        /* Run initialization */
        foreach (self::$loadedModules as $path) {
            self::runInitialization($path);
        }

        /* Run installation scripts */
        $installers = [];

        foreach (self::$loadedModules as $path) {
            $installer = new Installer($path);
            $installer->install();

            $installers[] = $installer;
        }

        /* Step 4: run all modules postInstallation */
        foreach ($installers as $installer) {
            $installer->finalize();
        }
    }

    /**
     * List files found in every module's $folder.
     * Newest files (i.e. declared in the LATEST included module) are returned FIRST
     */
    public static function listFiles($folder)
    {
        $allFiles = [];
        foreach (array_reverse(self::$loadedModules) as $path) {
            $allFiles = array_merge($allFiles, self::getFileList("$path/$folder"));
        }

        return $allFiles;
    }

    /**
     * Look for the given file in all modules.
     * Returns an array of all matching files, ordered showing newest modules first
     *
     */
    public static function getFile($filename)
    {
        $allFiles = [];
        foreach (array_reverse(self::$loadedModules) as $path) {
            $allFiles = array_merge($allFiles, self::getFileList($path, $filename));
        }

        return $allFiles;
    }


    private static function getFileList($folder, $pattern = "*.php", $subfoldersFirst = true, $rootFolder = null)
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
                if (self::matchesPattern($item, $pattern)) {
                    $files[] = "$rootFolder/$item";
                }
            } else {
                $folders[] = $item;
            }
        }

        $retval = [];

        if ($subfoldersFirst) {
            foreach ($folders as $subdir) {
                $retval = array_merge($retval, self::getFileList($subdir, $pattern, $subfoldersFirst, $rootFolder));
            }
            $retval = array_merge($retval, $files);
        } else {
            $retval = $files;
            foreach ($folders as $subdir) {
                $retval = array_merge($retval, self::getFileList($subdir, $pattern, $subfoldersFirst, $rootFolder));
            }
        }

        return $retval;
    }

    private static function matchesPattern($string, $pattern)
    {
        if ($pattern === null || $pattern === "*") {
            return true;
        }

        if ($pattern == "*.php" && substr($string, -4) == ".php") {
            return true;
        }

        return substr($string, strlen($pattern)*-1) == $pattern;
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