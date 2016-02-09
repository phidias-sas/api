<?php
namespace Phidias\Api;

class Action
{
    private $parsers;
    private $authentications;
    private $authorizations;
    private $validations;
    private $controllers;
    private $filters;
    private $interpreters;
    private $exceptionHandlers;

    private $accessControl;

    public function __construct()
    {
        $this->parsers           = [];
        $this->authentications   = [];
        $this->authorizations    = [];
        $this->validations       = [];
        $this->controllers       = [];
        $this->filters           = [];
        $this->interpreters      = [];
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
    public function parser($incomingContentType, $parser)
    {
        $this->parsers[$incomingContentType] = $parser;
        return $this;
    }

    public function authentication($authentication)
    {
        $this->authentications[] = $authentication;
        return $this;
    }

    public function authorization($authorization)
    {
        $this->authorizations[] = $authorization;
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

    public function interpreter($contentType, $action)
    {
        $this->interpreters[$contentType] = $action;
        return $this;
    }

    public function accessControl($control)
    {
        $this->accessControl = $control;
        return $this;
    }

    public function handler($exceptionClass, $callback)
    {
        $this->exceptionHandlers[$exceptionClass][] = $callback;
        return $this;
    }


    /* Getters */

    /**
    * Return a parser (one callback) responsible for
    * taking the incoming request body as a string
    * and producing an object (henceforth used as INPUT)
    */
    public function getParser($contentType)
    {
        return isset($this->parsers[$contentType]) ? $this->parsers[$contentType] : null;
    }

    public function getAuthentications()
    {
        return $this->authentications;
    }

    public function getAuthorizations()
    {
        return $this->authorizations;
    }

    public function getValidations()
    {
        return $this->validations;
    }

    public function getControllers()
    {
        return $this->controllers;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    /*
    * Return an interpreter (one callback) responsible for
    * taking the current OUTPUT object and producing a 
    * string in the specificed content type
    */
    public function getInterpreter($contentType)
    {
        return isset($this->interpreters[$contentType]) ? $this->interpreters[$contentType] : null;
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

        if (isset($array["parser"])) {
            foreach ($array["parser"] as $contentType => $callbacks) {
                foreach ((array)$callbacks as $callback) {
                    $action->parser($contentType, $callback);
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

        if (isset($array["interpreter"])) {
            foreach ($array["interpreter"] as $contentType => $callbacks) {
                foreach ((array)$callbacks as $callback) {
                    $action->interpreter($contentType, $callbacks);
                }
            }
        }

        if (isset($array["handler"])) {
            foreach ($array["handler"] as $exceptionClass => $callbacks) {
                foreach ((array)$callbacks as $callback) {
                    $action->handler($exceptionClass, $callback);
                }
            }
        }

        if (isset($array["access-control"])) {
            $action->accessControl($array["access-control"]);
        }

        return $action;
    }

}