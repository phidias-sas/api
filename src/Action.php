<?php
namespace Phidias\Api;

class Action
{
    private $parsers;
    private $authentication;
    private $authorization;
    private $validations;
    private $controllers;
    private $filters;
    private $renderers;
    private $exceptionHandlers;

    private $accessControl;

    public function __construct()
    {
        $this->parsers           = [];
        $this->authentication    = [];
        $this->authorization     = [];
        $this->validations       = [];
        $this->controllers       = [];
        $this->filters           = [];
        $this->renderers         = [];
        $this->exceptionHandlers = [];

        $this->accessControl     = null;
    }


    public static function factory($data)
    {
        if (!$data) {
            return new Action;
        }

        if (is_array($data)) {
            return self::fromArray($data);
        }

        if (is_a($data, "Phidias\Api\Action")) {
            return $data;
        }

        return (new Action)->controller($data);
    }


    /* Setters */
    public function parse($incomingContentType, $parser)
    {
        $this->parsers[$incomingContentType] = $parser;
        return $this;
    }

    public function authentication($authentication)
    {
        $this->authentication[] = $authentication;
        return $this;
    }

    public function authorization($authorization)
    {
        $this->authorization[] = $authorization;
        return $this;
    }

    public function validation($validation)
    {
        $this->validations[] = $validation;
        return $this;
    }

    public function controller($controller)
    {
        $this->controllers[] = $controller;
        return $this;
    }

    public function filter($callback)
    {
        $this->filters[] = $callback;
        return $this;
    }

    public function render($contentType, $action)
    {
        $this->renderers[$contentType] = $action;
        return $this;
    }

    public function accessControl($control)
    {
        $this->accessControl = $control;
        return $this;
    }

    public function handle($exceptionClass, $callback)
    {
        $this->exceptionHandlers[$exceptionClass][] = $callback;
        return $this;
    }


    /* Getters */
    public function getParser($contentType)
    {
        return isset($this->parsers[$contentType]) ? $this->parsers[$contentType] : null;
    }

    public function getAuthentication()
    {
        return $this->authentication;
    }

    public function getAuthorization()
    {
        return $this->authorization;
    }

    public function getValidations()
    {
        return $this->validations;
    }

    public function getControllers()
    {
        return $this->controllers;
    }

    public function getRenderer($contentType)
    {
        return isset($this->renderers[$contentType]) ? $this->renderers[$contentType] : null;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function getExceptionHandlers($exception)
    {
        $retval = [];

        foreach ($this->exceptionHandlers as $exceptionClass => $callback) {
            if (is_a($exception, $exceptionClass)) {
                $retval[] = $callback;
            }
        }

        return $retval;
    }

    public function getAccesscontrol()
    {
        return $this->accessControl ? AccessControl::factory($this->accessControl) : null;
    }

    private static function fromArray($array)
    {
        $action = new Action;

        if (isset($array["parse"])) {
            foreach ($array["parse"] as $contentType => $callbacks) {
                foreach ((array)$callbacks as $callback) {
                    $action->parse($contentType, $callback);
                }
            }
        }

        if (isset($array["authentication"])) {
            foreach ((array)$array["authentication"] as $authentication) {
                $action->authentication($authentication);
            }
        }

        if (isset($array["authorization"])) {
            foreach ((array)$array["authorization"] as $authorization) {
                $action->authorization($authorization);
            }
        }

        if (isset($array["validation"])) {
            foreach ((array)$array["validation"] as $validation) {
                $action->validation($validation);
            }
        }

        if (isset($array["controller"])) {
            foreach ((array)$array["controller"] as $controller) {
                $action->controller($controller);
            }
        }

        if (isset($array["filter"])) {
            foreach ( (array)$array["filter"] as $filter ) {
                $action->filter($filter);
            }
        }

        if (isset($array["catch"])) {
            foreach ($array["catch"] as $exceptionClass => $callbacks) {
                foreach ((array)$callbacks as $callback) {
                    $action->handle($exceptionClass, $callback);
                }
            }
        }

        if (isset($array["render"])) {
            foreach ($array["render"] as $contentType => $callbacks) {
                foreach ((array)$callbacks as $callback) {
                    $action->render($contentType, $callbacks);
                }
            }
        }

        if (isset($array["access-control"])) {
            $action->accessControl($array["access-control"]);
        }

        return $action;
    }

}