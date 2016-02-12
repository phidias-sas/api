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

    public function __construct()
    {
        $this->methodActions = [];
        $this->isAbstract    = false;
    }

    public function on($method, $action)
    {
        $this->methodActions[strtolower($method)][] = Action::factory($action);
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

    public function hasMethod($method)
    {
        return isset($this->methodActions["any"]) || isset($this->methodActions[strtolower($method)]);
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

    public function getActions($method)
    {
        $method = strtolower($method);
        $retval = [];

        if (isset($this->methodActions[$method])) {
            $retval = array_merge($retval, $this->methodActions[$method]);
        }

        if (isset($this->methodActions["any"])) {
            $retval = array_merge($retval, $this->methodActions["any"]);
        }

        return $retval;
    }

    public function merge($resource)
    {
        $this->isAbstract = $this->isAbstract && $resource->isAbstract;
        foreach ($resource->methodActions as $method => $actions) {
            $this->methodActions[$method] = isset($this->methodActions[$method]) ? array_merge($this->methodActions[$method], $actions) : $actions;
        }
        return $this;
    }

    public function setAttributes($attributes)
    {
        foreach ($this->methodActions as $method => &$actions) {
            foreach ($actions as &$action) {
                $action = Action::factory($action, $attributes);
            }
        }
        return $this;
    }

    public function getDispatcher($method)
    {
        $foundControllers = false;

        $dispatcher = new Dispatcher;
        foreach ($this->getActions($method) as $action) {
            $foundControllers = $foundControllers || count($action->getControllers());
            $dispatcher->add($action);
        }

        if (!$foundControllers) {
            throw new Server\Exception\MethodNotImplemented($this->getImplementedMethods());
        }

        return $dispatcher;
    }

    public function getAccessControl($method = null)
    {
        $control = new AccessControl;

        foreach ($this->getActions($method) as $action) {
            if ($customControl = $action->getAccessControl()) {
                $control->combine($customControl);
            }
        }

        return $control;
    }

}