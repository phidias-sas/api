<?php
namespace Phidias\Api\Dispatcher\Exception;

class AuthorizationException extends \Phidias\Api\Dispatcher\Exception
{
    protected static $statusCode = 403;
}