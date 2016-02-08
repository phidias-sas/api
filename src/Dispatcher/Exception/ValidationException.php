<?php
namespace Phidias\Api\Dispatcher\Exception;

class ValidationException extends \Phidias\Api\Dispatcher\Exception
{
    protected static $statusCode = 422;
}