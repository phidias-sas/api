<?php
namespace Phidias\Api\Dispatcher;

/*
$myFunction = Callback::factory("myClass->myFunction({arg1}, null, 1, true, {arg2})");

$result = $myFunction([
    "arg1" => "some string",
    "arg2" => ... an object! ....
]);
*/

class Callback
{
    private $callable;
    private $expectedArguments;

    public static function factory($data)
    {
        if (is_a($data, "Phidias\Api\Dispatcher\Callback")) {
            return $data;
        }

        return new Callback($data);
    }

    public function __construct($callback)
    {
        if (is_callable($callback)) {

            $this->callable          = $callback;
            $this->expectedArguments = [];

            $reflection = new \ReflectionFunction($callback);
            foreach ($reflection->getParameters() as $parameter) {
                $this->expectedArguments[] = '{'.$parameter->getName().'}';
            }

            return;
        }

        if (is_string($callback)) {
            $this->parseString($callback);
            return;
        }

        throw new Callback\InvalidCallbackException($callback);
    }

    public function invocable()
    {
        $callback = $this;

        return function($incomingArguments = null) use ($callback) {
            return $callback->run($incomingArguments);
        };
    }

    public function run($incoming = null)
    {
        if ($this->expectedArguments) {

            $arguments = [];
            foreach ($this->expectedArguments as $expectedArgument) {
                $arguments[] = $this->match($expectedArgument, $incoming);
            }

        } else {
            $arguments = is_array($incoming) ? $incoming : [$incoming];
        }

        try {
            return call_user_func_array($this->callable, $arguments);
        } catch (\Exception $e) {
            throw new Callback\ExecutionException($e, $this);
        }

    }

    public function getCallable()
    {
        return $this->callable;
    }

    private function match($value, $arguments)
    {
        if ($value === "" || strtolower($value) === "null") {
            return null;
        }

        if ($value[0] === "{") {
            $keyName = substr($value, 1, -1);
            //return isset($arguments[$keyName]) ? $arguments[$keyName] : null;
            return $this->getProperty($keyName, $arguments);
        }

        if (strtolower($value) === "true") {
            return true;
        }

        if (strtolower($value) === "false") {
            return false;
        }

        return $value;
    }


    /*
    arguments:
    request => this->request
    response => this->response
    object => {
        // some object!
        a: {
            b: {}
        }
    }

    getProperty("request")    --> this->request
    getProperty("object")     --> the object
    getProperty("object.a")   --> {b: {}}
    getProperty("object.a.n") --> {}

    */

    private function getProperty($key, $arguments)
    {
        $parts         = explode(".", $key);
        $currentTarget = $arguments;

        foreach ($parts as $part) {

            if (is_scalar($currentTarget)) {
                return null;
            }

            if (is_object($currentTarget)) {

                if (!isset($currentTarget->$part)) {
                    return null;
                }

                $currentTarget = $currentTarget->$part;

            } elseif (is_array($currentTarget)) {

                if (!isset($currentTarget[$part])) {
                    return null;
                }

                $currentTarget = $currentTarget[$part];

            }

        }

        return $currentTarget;
    }


    private function parseString($string)
    {
        $isStatic = strpos($string, "::") !== false;

        $string   = str_replace("::", "->", trim($string));
        $parts    = explode("->", $string);

        if (count($parts) !== 2) {
            throw new Callback\InvalidCallbackException("'$string' is not a valid callback string");
        }

        $classname   = $parts[0];
        $methodParts = explode("(", $parts[1], 2);
        $method      = $methodParts[0];

        $expectedArguments = str_replace(")", "", $methodParts[1]);
        $expectedArguments = array_map('trim', explode(",", $expectedArguments));

        if (!is_callable([$classname, $method])) {
            throw new Callback\InvalidCallbackException("$string is not callable");
        }

        if ($isStatic) {
            $callable = [$classname, $method];
        } else {
            $object   = new $classname;
            $callable = [$object, $method];
        }

        $this->callable          = $callable;
        $this->expectedArguments = $expectedArguments;

    }

}