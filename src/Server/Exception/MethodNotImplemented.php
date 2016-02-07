<?php
namespace Phidias\Api\Server\Exception;

class MethodNotImplemented extends \Exception
{
    private $implementedMethods;

    public function __construct($implementedMethods, $message = '', $code = 0, Exception $previous = null)
    {
        $this->implementedMethods = array_map('strtoupper', array_unique($implementedMethods));

        parent::__construct($message, $code, $previous);
    }

    public function getImplementedMethods()
    {
        return $this->implementedMethods;
    }
}