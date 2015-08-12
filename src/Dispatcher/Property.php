<?php 
namespace Phidias\Api\Dispatcher;

class Property
{
    public $value;
    public $arguments;

    public function __construct($value, $arguments = [])
    {
        $this->value     = $value;
        $this->arguments = $arguments;
    }
}