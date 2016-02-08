<?php
namespace Phidias\Api\Dispatcher\Exception;

class ParseException extends \Phidias\Api\Dispatcher\Exception
{
    protected static $statusCode = 415;
}