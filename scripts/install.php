<?php
namespace Phidias\Api\Server;

use Phidias\Db\Db;
use Phidias\Db\Orm\Entity\Reflection as EntityReflection;

class Instance
{
    private static $modules = [];

    public function import($path)
    {
        self::$modules[] = realpath($path);
    }

    public function execute()
    {
        $this->install();
    }

    public function run()
    {
        $this->install();
    }

    private function install()
    {
        Module::install(self::$modules);
    }

    public function onInitialize()
    {
    }

    public function onNotFound()
    {
    }

    public function onMethodNotImplemented()
    {
    }

    public function resource()
    {
    }

    public function find()
    {
    }

}


$index = realpath(".")."/index.php";

if (! is_file($index)) {
    echo "'$index' not found! Remember: you must run this script from the same path as your index.php\n";
    exit;
}

include $index;