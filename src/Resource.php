<?php
namespace Phidias\Api;

class Resource
{
    private $isAbstract;
    private $methodActions;

    public static function factory($resourceData)
    {
        if (!$resourceData) {
            return new Resource;
        }

        if (is_a($resourceData, "Phidias\Api\Resource")) {
            return $resourceData;
        }

        if (is_array($resourceData)) {
            return self::fromArray($resourceData);
        }

        trigger_error("could not create resource", E_USER_ERROR);
    }

    private static function fromArray($array)
    {
        $resource = new Resource;

        if (isset($array["abstract"]) && $array["abstract"]) {
            $resource->isAbstract();
        }

        foreach ($array as $key => $actionData) {
            if (in_array(strtolower($key), ["get", "post", "put", "delete", "any"])) {
                $resource->on(strtolower($key), $actionData);
            }
        }

        return $resource;
    }

    public function __construct($dispatchers = null)
    {
        $this->methodActions = [];
        $this->isAbstract    = false;
    }

    public function on($methodName, $action)
    {
        $this->methodActions[strtolower($methodName)][] = $action;

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

    public function hasMethod($methodName)
    {
        return isset($this->methodActions["any"]) || isset($this->methodActions[trim(strtolower($methodName))]);
    }

    public function getImplementedMethods()
    {
        $retval = [];
        foreach (array_keys($this->methodActions) as $method) {
            $method = strtoupper($method);
            if ($method != "ANY") {
                $retval[] = $method;
            }
        }

        return $retval;
    }

    public function getActions($methodName)
    {
        $retval = [];

        if (isset($this->methodActions["any"])) {
            $retval = array_merge($retval, $this->methodActions["any"]);
        }

        if (isset($this->methodActions[$methodName])) {
            $retval = array_merge($retval, $this->methodActions[$methodName]);
        }

        return $retval;
    }

}