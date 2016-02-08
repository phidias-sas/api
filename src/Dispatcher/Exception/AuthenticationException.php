<?php
namespace Phidias\Api\Dispatcher\Exception;

class AuthenticationException extends \Phidias\Api\Dispatcher\Exception
{
    protected static $statusCode = 401;
}