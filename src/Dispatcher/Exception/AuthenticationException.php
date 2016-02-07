<?php
namespace Phidias\Api\Dispatcher\Exception;

class AuthenticationException extends \Phidias\Api\Dispatcher\Exception
{
    public function filterResponse($response)
    {
        $response = parent::filterResponse($response);

        return $response->withStatus(401, get_class($this->originalException));
    }
}