<?php
namespace Phidias\Api\Dispatcher\Exception;

class ParseException extends \Phidias\Api\Dispatcher\Exception
{
    public function filterResponse($response)
    {
        $response = parent::filterResponse($response);

        return $response->withStatus(415, get_class($this->originalException));
    }    
}