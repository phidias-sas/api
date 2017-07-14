<?php
namespace Phidias\Documentation;

use Phidias\Json\Document;
use Phidias\Json\Schema;
use Phidias\Api\Server\Module;

class Controller
{
    /* specification index file (relative to module's root) */
    private static $specificationIndex = "/specification/index.json";

    public function main()
    {
        /* Find all loaded modules */
        $modules = [];

        foreach ( Module::getLoadedModules() as $module ) {
            if (is_file($module."/".self::$specificationIndex)) {
                $modules[] = Document::parse($module."/".self::$specificationIndex);
            }
        }

        return $modules;
    }
}