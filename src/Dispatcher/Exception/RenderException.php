<?php
namespace Phidias\Api\Dispatcher\Exception;

class RenderException extends \Phidias\Api\Dispatcher\Exception
{
    public function filterResponse($response)
    {
        $response = parent::filterResponse($response);
        return $response->withStatus(406, get_class($this->originalException));
    }    
}