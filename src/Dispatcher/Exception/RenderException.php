<?php
namespace Phidias\Api\Dispatcher\Exception;

class RenderException extends \Phidias\Api\Dispatcher\Exception
{
    protected static $statusCode = 406;
}