<?php 
namespace Phidias\Api\Http\Response\ClientError;

use Phidias\Api\Http\Response\ClientError;

class MethodNotAllowed extends ClientError
{
    protected $statusCode = 405;

    public function __construct($allowedMethods = [])
    {
        parent::__construct();

        if ($allowedMethods) {
            $this->setHeader("Allow", $allowedMethods);
        }
    }
}
