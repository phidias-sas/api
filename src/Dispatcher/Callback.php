<?php
namespace Phidias\Api\Dispatcher;

class Callback
{
    private $request;
    private $response;
    private $data;

    public function __construct($request, $response, &$data)
    {
        $this->request  = $request;
        $this->response = $response;
        $this->data     = &$data;
    }

    public function execute($callable, $arguments = [])
    {
        if (is_string($callable)) {

            $arguments["request"]       = $this->request->withAttributes($arguments);
            $arguments["request.data"]  = $this->request->getParsedBody();

            $arguments["response"]      = $this->response;
            $arguments["response.data"] = $this->data;

            list($callable, $arguments) = $this->toCallable($callable, $arguments);
        
        } else {
            $arguments = [
                $this->request->withAttributes($arguments),
                $this->response,
                &$this->data
            ];
        }

        if (!is_callable($callable)) {
            throw new Exception\InvalidCallback;
        }

        ob_start();
        $output = call_user_func_array($callable, $arguments);
        $stdOut = ob_get_contents(); //Keep in mind: If errors were triggerd via trigger_error during controller executions, they will be in $stdOut
        ob_end_clean();

        return $output === null ? $stdOut : $output;
    }

    /**
     * Given a string in the form someClass->someFunction()
     * return the corresponding valid callable.
     *
     * Setting argument values:
     * stringToCallback("someClass->someFunction({arg1}, {arg2})",  array("arg1" => $argument1Value, "arg2" => $argument2Value))
     *
     * If no valid callable is found a E_USER_ERROR will be triggered
     *
     */
    private function toCallable($string, $argumentValues = array())
    {
        $isStatic = strpos($string, "::") !== false;

        $string   = str_replace("::", "->", trim($string));
        $parts    = explode("->", $string);

        if (count($parts) !== 2) {
            trigger_error("Invalid callable '$string'", E_USER_ERROR);
        }

        $classname   = $parts[0];
        $methodParts = explode("(", $parts[1], 2);
        $method      = $methodParts[0];

        if (!is_callable(array($classname, $method))) {
            trigger_error("'$string' is not a valid callback", E_USER_ERROR);
        }

        $arguments         = array();
        $expectedArguments = str_replace(")", "", $methodParts[1]);
        $expectedArguments = explode(",", $expectedArguments);

        foreach ($expectedArguments as $argument) {

            $argument = trim($argument);

            if ($argument === "") {
                continue;
            }

            if ($argument[0] === "{") {
                $argumentName = substr($argument, 1, -1);
                $arguments[]  = isset($argumentValues[$argumentName]) ? $argumentValues[$argumentName] : null;
                continue;
            }

            if (strtolower($argument) === "null") {
                $arguments[] = null;
                continue;
            }

            if (strtolower($argument) === "true") {
                $arguments[] = true;
                continue;
            }

            if (strtolower($argument) === "false") {
                $arguments[] = false;
                continue;
            }


            $replaceKeys   = array();
            $replaceValues = array();

            foreach ($argumentValues as $argumentName => $argumentValue) {
                $replaceKeys[]   = "{".$argumentName."}";
                $replaceValues[] = is_string($argumentValue) ? $argumentValue : null;
            }

            $arguments[] = str_replace($replaceKeys, $replaceValues, $argument);
        }

        if ($isStatic) {
            $callable = array($classname, $method);
        } else {

            /* Special case: classname extends Phidias\Api\Resource\Controller */
            if (is_subclass_of($classname, "Phidias\Api\Resource\Controller")) {
                $object = new $classname($this->request, $this->response);
            } else {
                $object = new $classname;
            }
            
            $callable = array($object, $method);
        }

        return array($callable, $arguments);
    }


}