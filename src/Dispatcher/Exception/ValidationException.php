<?php
namespace Phidias\Api\Dispatcher\Exception;

class ValidationException extends \Phidias\Api\Dispatcher\Exception
{
    public function filterResponse($response)
    {
        $response = parent::filterResponse($response);

        $exceptionName = get_class($this->originalException);

        return $response->withStatus(422, $exceptionName != "Exception" ? $exceptionName: null);
    }    
}