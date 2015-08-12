<?php
namespace Phidias\Api\Environment;

use Phidias\Api\Http\Response;

interface EnvironmentInterface
{
    public static function getServerRequest();
    public static function sendResponse(Response $response);
}