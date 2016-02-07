<?php
namespace Phidias\Api\Dispatcher\Callback;

/*
Thrown when an error ocurs while running a callback
*/
class ExecutionException extends \Exception
{
    private $originalException;
    private $callback;

    public function __construct($originalException, $callback, $message = '', $code = 0, Exception $previous = null)
    {
        $this->originalException = $originalException;
        $this->callback          = $callback;

        parent::__construct($message, $code, $previous);
    }

    public function getOriginalException()
    {
        return $this->originalException;
    }

    public function getCallback()
    {
        return $this->callback;
    }

}