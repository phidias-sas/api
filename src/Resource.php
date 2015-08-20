<?php 
namespace Phidias\Api;

use Phidias\Api\Resource\Method;

class Resource
{
    private $arguments;
    private $dispatchers;
    private $isAbstract;

    public static function factory($arrayResourceData)
    {
        $resource = new Resource;

        if (isset($arrayResourceData["abstract"]) && $arrayResourceData["abstract"]) {
            $resource->isAbstract();
        }

        foreach ($arrayResourceData as $key => $arrayDispatcherData) {
            if (in_array(strtolower($key), ["get", "post", "put", "delete", "any"])) {
                $resource->method(strtolower($key), Dispatcher::factory($arrayDispatcherData));
            }
        }

        return $resource;
    }

    public function __construct($dispatchers = null)
    {
        $this->arguments   = [];
        $this->dispatchers = [];
        $this->isAbstract  = false;
    }

    public function method($methodName, Dispatcher $dispatcher)
    {
        $methodName = strtolower($methodName);
        $this->dispatchers[$methodName] = isset($this->dispatchers[$methodName]) ? $this->dispatchers[$methodName]->merge($dispatcher) : $dispatcher;

        return $this;
    }

    public function isAbstract()
    {
        $this->isAbstract = true;
        return $this;
    }

    public function getIsAbstract()
    {
        return $this->isAbstract;
    }

    public function dispatch(Http\ServerRequest $request)
    {
        return $this->getDispatcher($request->getMethod())->dispatch($request);
    }

    /**
     * Return the dispatcher in charge of executing the given method
     * 
     * @return Dispatcher
     * @throws Exception\MethodNotImplemented
     * 
     */
    public function getDispatcher($methodName)
    {
        $methodName = strtolower($methodName);

        if (!isset($this->dispatchers[$methodName])) {

            if (isset($this->dispatchers["any"])) {
                return $this->dispatchers["any"];
            }

            throw new Exception\MethodNotImplemented($methodName, $this->getImplementedMethods);
        }

        $genericDispatcher = isset($this->dispatchers["any"]) ? $this->dispatchers["any"] : null;

        return $genericDispatcher === null ? $this->dispatchers[$methodName] : $genericDispatcher->merge($this->dispatchers[$methodName]);
    }


    public function arguments(array $arguments)
    {
        $this->arguments = $arguments;
        foreach ($this->dispatchers as $dispatcher) {
            $dispatcher->setArguments($arguments);
        }

        return $this;
    }

    public function hasMethod($methodName)
    {
        return isset($this->dispatchers[trim(strtolower($methodName))]);
    }

    public function getImplementedMethods()
    {
        return array_map("strtoupper", array_keys($this->dispatchers));
    }

    public function merge($resource)
    {
        if ($resource == null) {
            return $this;
        }

        $this->isAbstract = $this->isAbstract && $resource->isAbstract;

        foreach ($resource->dispatchers as $methodName => $dispatcher) {
            $this->method($methodName, $dispatcher);
        }

        return $this;
    }

}