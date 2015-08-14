<?php
namespace Phidias\Api\Server\Module;

use Phidias\Api\Server\Module;

use Phidias\Db\Db;
use Phidias\Db\Orm\Entity\Reflection as EntityReflection;


class Installer
{
    private $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function install()
    {
        if (!is_file($installationFile = "{$this->path}/install.php")) {
            return;
        }

        include $installationFile;
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
            echo "No entities found";
            return;
        }

        echo "updating database:\n\n";

        Db::create($identifier);

        foreach ($targetEntities as $entityClassName) {
            try {
                $entityClassName::getSchema()->patch();
            } catch (\Exception $e) {
                echo $entityClassName.": ".$e->getMessage()."\n";
            }
        }

        foreach ($targetEntities as $entityClassName) {
            try {
                $entityClassName::getSchema()->createTriggers();
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }

    }

}