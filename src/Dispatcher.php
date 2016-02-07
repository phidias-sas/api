<?php
namespace Phidias\Api;

use Phidias\Utilities\Debugger;
use Phidias\Api\Dispatcher\Callback;

/**
 *
 * $dispatcher = new Dispatcher;
 * $dispatcher->add($action, $attributes);
 * $dispatcher->add($action, $attributes);
 * ....
 *
 * $response = $dispatcher->dispatch($request);
 */

class Dispatcher
{
    private $queue = [];

    private $request;
    private $response;
    private $input;
    private $output;

    private $callbackArguments; // the arguments to be sent to all callbacks

    public function add(Action $action, $attributes = null)
    {
        $this->queue[] = [$action, $attributes];
    }

    public function dispatch(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $this->request  = $request;
        $this->response = new Http\Response;
        $this->input    = null;
        $this->output   = null;

        $this->callbackArguments = [
            "request"  => &$this->request,
            "response" => &$this->response,
            "input"    => &$this->input,
            "output"   => &$this->output,
            "query"    => $this->getQueryObject()
        ];

        try {

            try {
                $this->runInputParser();
            } catch (\Exception $e) {
                throw new Dispatcher\Exception\ParseException($e);
            }

            try {
                $this->runAuthentication();
            } catch (\Exception $e) {
                throw new Dispatcher\Exception\AuthenticationException($e);
            }

            try {
                $this->runAuthorization();
            } catch (\Exception $e) {
                throw new Dispatcher\Exception\AuthorizationException($e);
            }

            try {
                $this->runValidation();
            } catch (\Exception $e) {
                throw new Dispatcher\Exception\ValidationException($e);
            }

            try {
                $this->runControllers();
            } catch (\Exception $e) {
                throw new Dispatcher\Exception\ControllerException($e);
            }

            try {
                $this->runFilters();
            } catch (\Exception $e) {
                throw new Dispatcher\Exception\FilterException($e);
            }

        } catch (Dispatcher\Exception $e) {

            $this->response = $e->filterResponse($this->response);

            try {

                $this->runExceptionHandlers($e->getOriginalException());

            } catch (\Exception $final) {

                // FUBAR.  An exception was thrown during exception handling
                $body = new Http\Stream("php://temp", "w");
                $body->write($final->getMessage());

                $this->response
                    ->status(500, get_class($final))
                    ->body($body);

            }

        }

        try {
            $this->render();
        } catch (\Exception $e) {
            $this->response->status(406, get_class($e));
            $this->renderAsJson();
        }


        $this->setAccessControl();

        return $this->response;
    }

    private function getCallbackArguments($attributes = null)
    {
        if (!$attributes) {
            return $this->callbackArguments;
        }

        $retval = array_merge($this->callbackArguments, $attributes);
        $retval["request"] = $retval["request"]->withAttributes($attributes);

        return $retval;
    }

    private function getQueryObject()
    {
        // The query string interpreted as an object
        $params = $this->request->getQueryParams();
        return $params ? json_decode(json_encode($params)) : null;
    }

    private function runInputParser()
    {
        $parser = null;
        $incomingMediaType = $this->request->getHeaderLine("content-type");

        foreach ($this->queue as $inchuchu) {

            $action = $inchuchu[0];

            $parser = $action->getParser($incomingMediaType);
            if ($parser) {
                break;
            }

        }

        if ($parser) {

            $arguments          = $this->getCallbackArguments();
            $arguments["input"] = (string)$this->request->getBody();

            $this->input = Callback::factory($parser)->run($arguments);
            return;
        }


        // Default input parser:
        // pass trought _POST if present, otherwise attempt to decode JSON

        $parsedBody = $this->request->getParsedBody();
        if ($parsedBody) {
            $this->input = $parsedBody;
            return;
        }

        if (isset($_POST) && !empty($_POST)) {
            $this->input = $_POST;
            return;
        }

        $inputString = (string)$this->request->getBody();
        $inputJson   = json_decode($inputString);

        $this->input = $inputJson ?: $inputString;

    }


    private function runAuthentication()
    {
        foreach ($this->queue as $inchuchu) {

            $action     = $inchuchu[0];
            $attributes = $inchuchu[1];

            $arguments = $this->getCallbackArguments($attributes);

            foreach ($action->getAuthentication() as $callback) {
                $retval = Callback::factory($callback)->run($arguments);
            }

        }
    }

    private function runAuthorization()
    {
        foreach ($this->queue as $inchuchu) {

            $action     = $inchuchu[0];
            $attributes = $inchuchu[1];

            $arguments = $this->getCallbackArguments($attributes);

            foreach ($action->getAuthorization() as $callback) {
                $retval = Callback::factory($callback)->run($arguments);

                if ($retval === false) {
                    throw new \Exception("interruption");
                }
            }

        }
    }

    private function runValidation()
    {
        foreach ($this->queue as $inchuchu) {

            $action     = $inchuchu[0];
            $attributes = $inchuchu[1];

            $arguments = $this->getCallbackArguments($attributes);

            foreach ($action->getValidations() as $callback) {

                $retval = Callback::factory($callback)->run($arguments);

                // if false is returned, throw an exception
                if ($retval === false) {
                    throw new \Exception("validation failed");
                }

                // if data is returned, use it as the output
                if (!is_bool($retval) && $retval) {
                    $this->output = $retval;
                    throw new \Exception("validation errors");
                }
            }

        }
    }

    private function runControllers()
    {
        foreach ($this->queue as $inchuchu) {

            $action     = $inchuchu[0];
            $attributes = $inchuchu[1];

            $arguments = $this->getCallbackArguments($attributes);

            foreach ($action->getControllers() as $callback) {

                $result = Callback::factory($callback)->run($arguments);

                if ($result instanceOf \Psr\Http\Message\ResponseInterface) {
                    $this->response = $result;
                } elseif ($result !== null) {
                    $this->output = $result;
                }

            }

        }

    }

    private function runFilters()
    {
        foreach ($this->queue as $inchuchu) {

            $action     = $inchuchu[0];
            $attributes = $inchuchu[1];

            $arguments = $this->getCallbackArguments($attributes);

            foreach ($action->getFilters() as $callback) {

                $result = Callback::factory($callback)->run($arguments);

                if ($result instanceOf \Psr\Http\Message\ResponseInterface) {
                    $this->response = $result;
                } elseif ($result !== null) {
                    $this->output = $result;
                }

            }

        }

    }

    private function runExceptionHandlers($exception)
    {
        foreach ($this->queue as $inchuchu) {

            $action     = $inchuchu[0];
            $attributes = $inchuchu[1];

            $arguments              = $this->getCallbackArguments($attributes);
            $arguments["exception"] = $exception;


            foreach ($action->getExceptionHandlers($exception) as $callbacks) {

                foreach ($callbacks as $callback) {
                    $result = Callback::factory($callback)->run($arguments);

                    if ($result instanceOf \Psr\Http\Message\ResponseInterface) {
                        $this->response = $result;
                    }
                }

            }

        }
    }

    private function setAccessControl()
    {
        foreach ($this->queue as $inchuchu) {
            $action = $inchuchu[0];
            if ($control = $action->getAccessControl()) {
                $this->response = $control->filter($this->response, $this->request);
            }
        }
    }

    private function render()
    {
        Debugger::startBlock("rendering response data");

        $acceptedMediaTypes   = $this->getAcceptedMediaTypes($this->request);
        $acceptedMediaTypes[] = "application/json";

        $renderer         = null;
        $foundContentType = null;

        foreach ($this->queue as $inchuchu) {

            $action = $inchuchu[0];

            foreach ($acceptedMediaTypes as $mediaType) {
                $renderer = $action->getRenderer($mediaType);
                if ($renderer) {
                    $foundContentType = $mediaType;
                    break 2;
                }
            }
        }


        $body = new Http\Stream("php://temp", "w");
        $this->response->body($body);

        // No renderer: write output as JSON
        if (!$renderer) {

            $this->response->header("Content-Type", "application/json; charset=utf-8");
            $body->write(json_encode($this->output, JSON_PRETTY_PRINT));

        } elseif (is_file($renderer)) {
            
            $this->response->header("Content-Type", "$foundContentType; charset=utf-8");
            $body->write($this->renderFile($renderer));

        } else {

            $callback = Callback::factory($renderer);
            $string = $callback->run($this->getCallbackArguments());

            $this->response->header("Content-Type", "$foundContentType; charset=utf-8");
            $body->write($string);

        }

        Debugger::endBlock();

    }

    private function renderFile($filename)
    {
        foreach ($this->callbackArguments as $name => $value) {
            $$name = $this->callbackArguments[$name];
        }

        ob_start();
            include $filename;
            $stdout = ob_get_contents();
        ob_end_clean();

        return $stdout;
    }


    private function getAcceptedMediaTypes($request)
    {
        if (!$request->hasHeader("Accept")) {
            return ["application/json"];
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

}