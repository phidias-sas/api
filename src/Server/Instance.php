<?php
namespace Phidias\Api\Server;

use Phidias\Utilities\Debugger as Debug;

use Phidias\Api\Router;
use Phidias\Api\Resource;
use Phidias\Api\Dispatcher;
use Phidias\Api\Environment;

use Phidias\Api\Http\ServerRequest;
use Phidias\Api\Http\Response;
use Phidias\Api\Http\Stream;

class Instance
{
    private $router;

    private $onNotFound;
    private $onMethodNotImplemented;

    private $isInitialized;
    private $initializationCallbacks;

    public $accessControl;

    public function __construct()
    {
        $this->router                  = new Router;
        $this->accessControl           = new AccessControl;

        $this->isInitialized           = false;
        $this->initializationCallbacks = [];
    }

    public function onInitialize($callback)
    {
        if (!is_callable($callback)) {
            trigger_error("Server::onInitialize: callback is not valid", E_USER_ERROR);
        }

        $this->initializationCallbacks[] = $callback;
    }

    private function initialize()
    {
        if ($this->isInitialized) {
            return $this;
        }

        $this->isInitialized = true;

        foreach ($this->initializationCallbacks as $callback) {
            $callback();
        }
    }

    /* Error events */
    public function onNotFound(Dispatcher $dispatcher)
    {
        $this->onNotFound = $dispatcher;
    }

    public function onMethodNotImplemented(Dispatcher $dispatcher)
    {
        $this->onMethodNotImplemented = $dispatcher;
    }

    public function resource($path, $resource = null)
    {
        if (is_array($path)) {
            return $this->resourcesFromArray($path);
        }

        if (is_array($resource)) {
            $resource = Resource::factory($resource);
        }

        $this->router->store($path, $resource);

        return $this;
    }

    public function find($url)
    {
        $results = $this->router->find($url);

        if (! $results) {
            throw new Exception\ResourceNotFound;
        }

        $retval = null;

        $count = count($results);
        for ($cont = $count-1; $cont >= 0; $cont--) {

            $resource = $results[$cont]["data"]->arguments($results[$cont]["arguments"]);

            if ($retval == null) {
                $retval = $resource;
            } else {
                $retval->merge($resource);
            }

        }

        if ($retval->getIsAbstract()) {
            throw new Exception\ResourceNotFound;
        }

        return $retval;
    }


    /**
     * Execute a server request.
     *
     * @param ServerRequest
     * @return Response
     */
    public function execute(ServerRequest $request)
    {
        $this->initialize();

        try {

            $method = strtoupper($request->getMethod());
            $path   = $request->getUri()->getPath();


            Debug::startBlock("$method $path", "resource");

            $resource = $this->find($path);
            $response = $resource->dispatch($request);

            Debug::endBlock();

        } catch (Exception\ResourceNotFound $e) {

            if ($this->onNotFound == null) {
                $response = new Response(404);
            } else {
                $response = $this->onNotFound->dispatch($request);
            }

        } catch (Resource\Exception\MethodNotImplemented $e) {

            if ($this->onMethodNotImplemented == null) {
                $response = (new Response(405))->withHeader("Allowed", $e->getImplementedMethods());
            } else {
                $response = $this->onMethodNotImplemented->dispatch($request);
            }

        } catch (\Exception $e) {

            $body = new Stream("php://temp", "w");
            $body->write("<pre>" . print_r($e, true));

            $response = (new Response())
                ->status(500, get_class($e))
                ->body($body);

        }

        return $this->postProcess($response, $request);
    }


    /**
     * Final processing before response is returned
     *
     * @param ServerRequest
     * @param Response
     * @return Response
     */
    private function postProcess($response, $request)
    {
        return $this->accessControl->filter($response, $request);
    }

    /**
     * Marshall the current request from the environment, execute it and relay the response
     */
    public function run()
    {
        Environment::sendResponse(
            $this->execute(Environment::getServerRequest())
        );
    }

    /**
     * Import resources and templates from the given
     * module folder
     */
    public function import($path)
    {
        Module::load($this, $path);
        return $this;
    }

    private function resourcesFromArray($resources)
    {
        foreach ($resources as $url => $arrayResourceData) {
            $this->resource($url, Resource::factory($arrayResourceData));
        }

        return $this;
    }
}