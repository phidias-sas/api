<?php
namespace Phidias\Api;

use Phidias\Api\Dispatcher\Exception;
use Phidias\Api\Dispatcher\Property;
use Phidias\Api\Dispatcher\Callback;
use Phidias\Api\Dispatcher\DispatcherInterface;
use Phidias\Api\Dispatcher\AccessControl;


use Phidias\Utilities\Debugger;

class Dispatcher implements DispatcherInterface
{
    private $authorization;
    private $authentication;
    private $validators;
    private $controller;
    private $templates;
    private $filters;
    private $exceptions;
    private $accessControl;

    private $templateEngineClass;

    private $request;
    private $response;
    private $data;

    public function __construct()
    {
        $this->authorization       = null;
        $this->authentication      = null;
        $this->validators          = [];
        $this->controller          = null;
        $this->templates           = [];
        $this->filters             = [];
        $this->exceptions          = [];
        $this->accessControl       = [];

        $this->templateEngineClass = "Phidias\Api\Dispatcher\TemplateEngine";
    }


    public static function factory($array)
    {
        $dispatcher = new Dispatcher;

        if (isset($array["authentication"])) {
            $dispatcher->authentication($array["authentication"]);
        }

        if (isset($array["authorization"])) {
            $dispatcher->authorization($array["authorization"]);
        }

        if (isset($array["validate"])) {
            foreach ( (array)$array["validate"] as $validator ) {
                $dispatcher->validator($validator);
            }
        }

        if (isset($array["controller"])) {
            $dispatcher->controller($array["controller"]);
        }

        if (isset($array["template"])) {

            if (is_string($array["template"])) {
                $dispatcher->template($array["template"]);
            } else {

                foreach ($array["template"] as $mimetype => $templateData) {

                    if (is_string($templateData)) {
                        $dispatcher->template($templateData, $mimetype);
                    } else {
                        foreach ($templateData as $objectType => $templateFile) {
                            $dispatcher->template($templateFile, $mimetype, $objectType);
                        }
                    }

                }

            }

        }

        if (isset($array["filter"])) {
            foreach ( (array)$array["filter"] as $filter ) {
                $dispatcher->filter($filter);
            }
        }

        if (isset($array["catch"])) {
            foreach ($array["catch"] as $exceptionClass => $callback) {
                $dispatcher->handle($exceptionClass, $callback);
            }
        }

        if (isset($array["access-control"])) {
            $dispatcher->accessControl($array["access-control"]);
        }

        return $dispatcher;
    }


    /**
     * Perform voodoo to obtain a Response for the given ServerRequest
     *
     * @return Http\Response
     */
    public function dispatch(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $this->request  = $request;
        $this->response = new Http\Response();

        try {

            $this->authenticate();
            $this->authorize();
            $this->validate();

            $this->execute();
            $this->runFilters();
            $this->setAccessControl();

            $this->render();

        } catch (\Exception $exception) {

            // See what the handler can do, and
            // catch default exceptions when it doesn't

            try {
                $this->handleException($exception);

            } catch (Exception\AuthenticationException $exception) {
                $this->response->status(401); //Unauthorized

            } catch (Exception\AuthorizationException $exception) {
                $this->response->status(403); //Forbidden

            } catch (Exception\ValidationException $exception) {
                $this->response->status(422); //UnprocessableEntity

            } catch (Exception\RenderException $exception) {
                $this->response->status(406); //Not Acceptable
            }

        }

        return $this->response;
    }


    private function authenticate()
    {
        if ($this->authentication === null) {
            return;
        }

        if (! $this->executeCallback($this->authentication->value, $this->authentication->arguments)) {
            throw new Exception\AuthenticationException;
        }
    }

    private function authorize()
    {
        if ($this->authorization === null) {
            return;
        }

        if (! $this->executeCallback($this->authorization->value, $this->authorization->arguments)) {
            throw new Exception\AuthorizationException;
        }
    }

    private function validate()
    {
        foreach ($this->validators as $validator) {
            if (false === $this->executeCallback($validator->value, $validator->arguments)) {
                throw new Exception\ValidationException;
            }
        }
    }

    private function execute()
    {
        if ($this->controller === null) {
            return;
        }

        Debugger::startBlock("running controller ".print_r($this->controller->value, true));

        $this->data = $this->executeCallback($this->controller->value, $this->controller->arguments);

        Debugger::endBlock();

    }

    private function render()
    {
        Debugger::startBlock("rendering response data");

        $template = $this->findSuitableTemplate($this->getAcceptedMediaTypes($this->request), $this->data);

        //!!! no template found.
        if ($template === null) {

            Debugger::add("no template found: encoding as json");

            $body = new Http\Stream("php://temp", "w");
            $body->write(json_encode($this->data, JSON_PRETTY_PRINT));

            $this->response
                ->header("Content-Type", "application/json")
                ->body($body);

            Debugger::endBlock();
            return;
        }

        Debugger::add("using template '$template'");

        $engine = $this->getTemplateEngine();

        $engine->assign("data", $this->data);
        $engine->assign("request", $this->request);
        $engine->assign("response", $this->response);

        $body = new Http\Stream("php://temp", "w");
        $body->write($engine->render($template));

        $this->response->body($body);

        Debugger::endBlock();
    }

    private function getAcceptedMediaTypes($request)
    {
        if (!$request->hasHeader("Accept")) {
            return [];
        }

        foreach ($request->getHeader("Accept") as $fullMediaType) {

            $parts     = explode(";q=", $fullMediaType);
            $mediaType = $parts[0];
            $priority  = isset($parts[1]) ? $parts[1] : 1;

            if ($priority) {
                $acceptedMediaTypes[] = $parts[0];
            }
        }

        return $acceptedMediaTypes;
    }

    private function getDataTypes($data)
    {
        $retval = [];

        $datatype = gettype($data);

        if ($datatype === "object") {

            $class    = get_class($data);
            $retval[] = $class;

            while ($class = get_parent_class($class)) {
                $retval[] = $class;
            }
        }

        $retval[] = $datatype;

        return $retval;
    }

    private function getTemplateEngine()
    {
        return new $this->templateEngineClass;
    }

    private function findSuitableTemplate($acceptedMediaTypes, $data)
    {
        $acceptedMediaTypes[] = "any";

        $dataTypes   = $this->getDataTypes($data);
        $dataTypes[] = "any";

        foreach ($acceptedMediaTypes as $mediaType) {
            foreach ($dataTypes as $dataType) {

                if (isset($this->templates[$mediaType][$dataType])) {
                    return $this->templates[$mediaType][$dataType];
                }

            }
        }

        return null;
    }

    private function runFilters()
    {
        foreach ($this->filters as $filterProperty) {

            $retval = $this->executeCallback($filterProperty->value, $filterProperty->arguments);

            if (is_a($retval, "Phidias\Api\Http\Response")) {
                $this->response = $retval;
            }

        }
    }

    private function setAccessControl()
    {
        foreach ($this->accessControl as $ruleProperty) {
            AccessControl::factory($ruleProperty->value)->filter($this->response, $this->request);
        }
    }

    private function handleException($exception)
    {
        foreach ($this->exceptions as $exceptionHandler) {

            list($exceptionClass, $callback) = $exceptionHandler->value;

            if (is_a($exception, $exceptionClass)) {
                return $this->executeCallback($callback, array_merge($exceptionHandler->arguments, [
                    "exception" => $exception
                ]));
            }
        }

        throw $exception;
    }


    /* Setters*/

    public function authorization($authorization)
    {
        $this->authorization = new Property($authorization);
        return $this;
    }

    public function authentication($authentication)
    {
        $this->authentication = new Property($authentication);
        return $this;
    }

    public function validator($validator)
    {
        $this->validators[] = new Property($validator);
        return $this;
    }

    public function controller($controller)
    {
        $this->controller = new Property($controller);
        return $this;
    }

    public function accessControl($rule)
    {
        $this->accessControl[] = new Property($rule);
    }

    public function template($template, $mimetype = null, $datatype = null)
    {
        if (!is_file($template)) {
            trigger_error("'$template' is not a valid file", E_USER_ERROR);
        }

        if ($mimetype === null) {
            $mimetype = "any";
        }

        if ($datatype === null) {
            $datatype = "any";
        }

        if (! isset($this->templates[$mimetype])) {
            $this->templates[$mimetype] = [];
        }

        if (! isset($this->templates[$mimetype][$datatype])) {
            $this->templates[$mimetype][$datatype] = [];
        }

        $this->templates[$mimetype][$datatype] = $template;

        return $this;
    }

    public function filter($filter)
    {
        $this->filters[] = new Property($filter);
        return $this;
    }

    public function handle($exceptionClass, $callback)
    {
        $this->exceptions[] = new Property([$exceptionClass, $callback]);
        return $this;
    }



    /* Callback interpreter */
    private function executeCallback($callable, $arguments = [])
    {
        return (new Callback($this->request, $this->response, $this->data))->execute($callable, $arguments);
    }



    /* Dispatcher management */

    /**
     * Combine with a second dispatcher
     */
    public function merge(DispatcherInterface $dispatcher)
    {
        if ($dispatcher->authorization !== null) {
            $this->authorization = $dispatcher->authorization;
        }

        if ($dispatcher->authentication !== null) {
            $this->authentication = $dispatcher->authentication;
        }

        $this->validators = array_merge($this->validators, $dispatcher->validators);

        if ($dispatcher->controller !== null) {
            $this->controller = $dispatcher->controller;
        }

        $this->templates     = array_merge($this->templates, $dispatcher->templates);
        $this->filters       = array_merge($this->filters, $dispatcher->filters);
        $this->exceptions    = array_merge($this->exceptions, $dispatcher->exceptions);
        $this->accessControl = array_merge($this->accessControl, $dispatcher->accessControl);

        return $this;
    }


    public function setArguments(array $arguments = [])
    {
        //Go through all properties and set the arguments
        $this->setArgumentsToProperty($this->authorization, $arguments);
        $this->setArgumentsToProperty($this->authentication, $arguments);
        $this->setArgumentsToProperty($this->validators, $arguments);
        $this->setArgumentsToProperty($this->controller, $arguments);
        $this->setArgumentsToProperty($this->filters, $arguments);
        $this->setArgumentsToProperty($this->exceptions, $arguments);
    }

    private function setArgumentsToProperty($property, $arguments)
    {
        if ($property === null) {
            return;
        }

        if (is_array($property)) {
            foreach ($property as $p) {
                $this->setArgumentsToProperty($p, $arguments);
            }
            return;
        }

        $property->arguments = $arguments;
    }

}