<?php
namespace Phidias\Api\Dispatcher;

/**
 * This encapsulates exception thrown during each dispatcher stage.
 * 
 * This class is extended by each stage exception (\Phidias\Api\Dispatcher\Exception\WhateverException)
 * 
 */
class Exception extends \Exception
{
    protected static $statusCode = 500;
    protected $originalException;
    private $callback;

    public function __construct($originalException, $message = '', $code = 0, Exception $previous = null)
    {
        if (is_a($originalException, 'Phidias\Api\Dispatcher\Callback\ExecutionException')) {
            $this->originalException = $originalException->getOriginalException();  //Inxception
            $this->callback          = $originalException->getCallback();
        } else {
            $this->originalException = $originalException;
        }

        parent::__construct($message, $code, $previous);
    }

    public function getOriginalException()
    {
        return $this->originalException;
    }

    public function override($response)
    {
        if ( !$response->getStatusCode() ) {

            /*
            keep in mind:
            self::$statusCode refers to the value declared in this file (500)
            get_called_class()::$statusCode  would be the value declared in the class that extends this exception
            */
            $className = get_called_class();
            $response->status($className::$statusCode, get_class($this->originalException));
        }

        if ($this->callback) {

            $closure = $this->callback->getCallable();

            if (is_array($closure)) {
                $reflection = new \ReflectionMethod($closure[0], $closure[1]);
            } else {
                $reflection = new \ReflectionFunction($closure);
            }

            $response->header("X-Phidias-Exception-Filename", $reflection->getFilename() . ' Line: ' . $reflection->getStartLine());
        }

        if ($message = $this->originalException->getMessage()) {
            $response->header("X-Phidias-Exception-Message", $message);
        }

        return $response;
    }
}