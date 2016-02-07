<?php 
namespace Phidias\Api\Index;

class Result
{
    public $data;
    public $attributes;

    public function __construct($data, $attributes)
    {
        $this->data       = $data;
        $this->attributes = $attributes;
    }
}