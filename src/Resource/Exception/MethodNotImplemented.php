<?php
namespace Phidias\Api\Resource\Exception;

class MethodNotImplemented extends \Exception
{
    private $implementedMethods;

    public function __construct($methodName, $implementedMethods = [])
    {
        $this->implementedMethods = $implementedMethods;

        parent::__construct();
    }

    public function getImplementedMethods()
    {
        return $this->implementedMethods;
    }
}