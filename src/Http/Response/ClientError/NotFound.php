<?php 
namespace Phidias\Api\Http\Response\ClientError;

use Phidias\Api\Http\Response\ClientError;

class NotFound extends ClientError
{
    protected $statusCode = 404;
}
