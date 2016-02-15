<?php
namespace Phidias\Api\Server\Module;

use Phidias\Api\Server\Module;

use Phidias\Db\Db;
use Phidias\Db\Orm\Entity\Reflection as EntityReflection;


class Installer
{
    private $path;
    private $postInstallationCallbacks;

    public function __construct($path)
    {
        $this->path                      = $path;
        $this->postInstallationCallbacks = [];
    }

    public function afterInstall($callback)
    {
        if (!is_callable($callback)) {
            trigger_error("invalid callback", E_USER_ERROR);
        }

        $this->postInstallationCallbacks[] = $callback;
    }

    public function install()
    {
        if (!is_file($installationFile = "{$this->path}/install.php")) {
            return;
        }

        $parts       = explode("/", trim($this->path, "/"));
        $packageName = array_pop($parts);
        $vendorName  = array_pop($parts);
        $moduleName  = ($vendorName ? "$vendorName/" : '').$packageName;
        echo "\n\n\nInstalling module \"{$moduleName}\"\n\n$installationFile\n";
        include $installationFile;
    }

    public function finalize()
    {
        foreach ($this->postInstallationCallbacks as $callback) {
            call_user_func($callback);
        }
    }

    public function createDatabase($identifier = null)
    {
        $sourcesFolder = $this->path."/".Module::DIR_SOURCES;

        if (!is_dir($sourcesFolder)) {
            return;
        }

        $targetEntities = [];

        foreach (EntityReflection::getEntities($sourcesFolder) as $entityClassName) {
            if ($entityClassName::getSchema()->getDb() == $identifier) {
                $targetEntities[] = $entityClassName;
            }
        }

        if (!$targetEntities) {
            echo "No entities found (no tables to create)";
            return;
        }

        $databaseSettings = Db::getCredentials($identifier);
        $databaseString   = $databaseSettings["username"].($databaseSettings["password"] ? ':****' : '').'@'.$databaseSettings["host"].'/'.$databaseSettings["database"];
        echo "Updating database $databaseString\n";

        Db::create($identifier);

        foreach ($targetEntities as $entityClassName) {
            echo "    pathing table for $entityClassName\n";
            try {
                $entityClassName::getSchema()->patch();
            } catch (\Exception $e) {
                echo $entityClassName.": ".$e->getMessage()."\n";
            }
        }

        foreach ($targetEntities as $entityClassName) {
            try {
                echo "    checking triggers for $entityClassName\n";
                $entityClassName::getSchema()->createTriggers();
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }

    }

}