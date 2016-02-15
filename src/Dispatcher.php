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
    private $authentication;

    private $callbackArguments; // the arguments to be sent to all callbacks

    public function __construct($actions = [])
    {
        foreach ($actions as $action) {
            $this->add($action);
        }
    }

    public function add(Action $action)
    {
        $this->queue[] = $action;
    }

    public function dispatch(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $this->request        = $request;
        $this->response       = new Http\Response;
        $this->input          = null;
        $this->output         = null;
        $this->authentication = null;

        $this->callbackArguments = [
            "request"        => &$this->request,
            "response"       => &$this->response,
            "input"          => &$this->input,
            "output"         => &$this->output,
            "authentication" => &$this->authentication,
            "query"          => $this->getQueryObject()
        ];

        $this->setAccessControl();

        /* Slide! */
        try {

            try {
                $this->runInputParser();
                $this->request = $this->request->withParsedBody($this->input);
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
                // The exception is now the output, an will be passed throug
                // the content type interpreter
                $this->response->status(500, get_class($final));
                $this->output = $final;

            }

        }

        // Try to render the output
        try {
            $this->renderOutput();
        } catch (\Exception $e) {
            $renderException = new Dispatcher\Exception\RenderException($e);
            $this->response = $renderException->filterResponse($response);
        }

        return $this->response;
    }

    private function getCallbackArguments($action = null)
    {
        if (!$action) {
            return $this->callbackArguments;
        }

        $attributes        = $action->getAttributes();
        $retval            = array_merge($this->callbackArguments, $attributes);
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
        $incomingMediaType = $this->request->getHeaderLine("content-type");
        $parser            = null;

        foreach ($this->queue as $action) {
            $parser = $action->getParser($incomingMediaType);
            if ($parser) {
                break;
            }
        }

        if ($parser) {
            $arguments          = $this->getCallbackArguments();
            $arguments["input"] = (string)$this->request->getBody();
            $this->input        = Callback::factory($parser)->run($arguments);
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
        foreach ($this->queue as $action) {
            $arguments = $this->getCallbackArguments($action);
            foreach ($action->getAuthentications() as $callback) {

                $result = Callback::factory($callback)->run($arguments);

                if ($result instanceOf \Psr\Http\Message\ResponseInterface) {
                    $this->response = $result;
                } elseif ($result !== null) {
                    $this->authentication = $result;
                }
            }
        }
    }

    private function runAuthorization()
    {
        foreach ($this->queue as $action) {
            $arguments = $this->getCallbackArguments($action);

            foreach ($action->getAuthorizations() as $callback) {
                $retval = Callback::factory($callback)->run($arguments);

                // if false is returned, thrown an authorization exception
                if ($retval === false) {
                    throw new \Exception("authorization failed");
                }

                // if data is returned, use it as the output
                if (!is_bool($retval) && $retval) {
                    $this->output = $retval;
                    throw new \Exception("authorization errors");
                }
            }
        }
    }

    private function runValidation()
    {
        foreach ($this->queue as $action) {
            $arguments = $this->getCallbackArguments($action);

            foreach ($action->getValidations() as $callback) {
                $retval = Callback::factory($callback)->run($arguments);

                // if false is returned, throw a validation exception
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
        foreach ($this->queue as $action) {
            $arguments = $this->getCallbackArguments($action);

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
        foreach ($this->queue as $action) {
            $arguments = $this->getCallbackArguments($action);

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
        foreach ($this->queue as $action) {
            $arguments              = $this->getCallbackArguments($action);
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

    private function renderOutput($allowCustomCallbacks = true)
    {
        // If the response already has data, ignore
        if (!!$this->response->getBody()) {
            return;
        }

        Debugger::startBlock("rendering response data");

        $acceptedMediaTypes   = $this->getAcceptedMediaTypes($this->request);
        $acceptedMediaTypes[] = "application/json";

        $interpreter = null;
        $mediaType   = null;

        if ($allowCustomCallbacks) {
            foreach ($this->queue as $action) {
                foreach ($acceptedMediaTypes as $mediaType) {
                    $interpreter = $action->getInterpreter($mediaType);
                    if ($interpreter) {
                        break 2;
                    }
                }
            }
        }

        $body = new Http\Stream("php://temp", "w");
        $this->response->body($body);

        // No interpreter: write output as JSON
        if (!$interpreter) {

            $this->response->header("Content-Type", "application/json; charset=utf-8");
            $body->write(!empty($this->output) ? json_encode($this->output, JSON_PRETTY_PRINT) : null);

        } elseif (is_string($interpreter) && file_exists($interpreter)) {

            $this->response->header("Content-Type", "$mediaType; charset=utf-8");
            $body->write($this->renderFile($interpreter));

        } else {

            $callback = Callback::factory($interpreter);
            $string = $callback->run($this->getCallbackArguments($action));

            $this->response->header("Content-Type", "$mediaType; charset=utf-8");
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

    private function setAccessControl()
    {
        $control = new AccessControl;

        foreach ($this->queue as $action) {
            if ($customControl = $action->getAccessControl()) {
                $control->combine($customControl);
            }
        }

        $this->response = $control->filter($this->response, $this->request);
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