<?php
namespace Phidias\Api;

use Phidias\Utilities\Debugger as Debug;

use Phidias\Api\Server\Module;

use Phidias\Api\Http\ServerRequest;
use Phidias\Api\Http\Response;
use Phidias\Api\Http\Stream;

class Server
{
    private static $router;
    private static $isInitialized           = false;
    private static $initializationCallbacks = [];

    private static $onNotFound;
    private static $onMethodNotImplemented;

    public static function onInitialize($callback)
    {
        if (!is_callable($callback)) {
            trigger_error("Server::onInitialize: callback is not valid", E_USER_ERROR);
        }

        self::$initializationCallbacks[] = $callback;
    }

    /* Error events */
    public static function onNotFound(Dispatcher $dispatcher)
    {
        self::$onNotFound = $dispatcher;
    }

    public static function onMethodNotImplemented(Dispatcher $dispatcher)
    {
        self::$onMethodNotImplemented = $dispatcher;
    }

    /**
     * Declare a resource
     * 
     * Server::resource("/myresource", <Resource>);
     * 
     * or
     * 
     * Server::resource([
     *    "/myresourceone"   => <Resource>
     *    "/myresourcetwo"   => <Resource>
     *    "/myresourcethree" => <Resource>
     * ])
     */
    public static function resource($path, $resource = null)
    {
        if (is_array($path)) {
            foreach ($path as $url => $resourceDeclaration) {
                self::resource($url, $resourceDeclaration);
            }
            return;
        }

        if (is_array($resource)) {
            $resource = Resource::factory($resource);
        }

        if (self::$router === null) {
            self::$router = new Router;
        }

        self::$router->store($path, $resource);
    }

    /**
     * Import resources and templates from the given
     * module folder
     */
    public static function import($path)
    {
        Module::load($path);
    }

    /**
     * Execute a server request.
     *
     * @param ServerRequest
     * @return Response
     */
    public static function execute(ServerRequest $request)
    {
        self::initialize();

        try {

            $method = strtoupper($request->getMethod());
            $path   = urldecode($request->getUri()->getPath());

            Debug::startBlock("$method $path", "resource");

            $resource = self::find($path);
            $response = $resource->dispatch($request);

            Debug::endBlock();

        } catch (Server\Exception\ResourceNotFound $e) {

            if (self::$onNotFound == null) {
                $response = new Response(404);
            } else {
                $response = self::$onNotFound->dispatch($request);
            }

        } catch (Resource\Exception\MethodNotImplemented $e) {

            if (self::$onMethodNotImplemented == null) {
                $response = (new Response(405))->withHeader("Allowed", $e->getImplementedMethods());
            } else {
                $response = self::$onMethodNotImplemented->dispatch($request);
            }

        } catch (\Exception $e) {

            $body = new Stream("php://temp", "w");
            $body->write($e->getMessage());

            $response = (new Response())
                ->status(500, get_class($e))
                ->body($body);

        }

        return $response;
    }


    /**
     * Obtain the current request from the environment, execute it and relay the response
     */
    public static function run()
    {
        Environment::sendResponse(
            self::execute(Environment::getServerRequest())
        );
    }


    private static function initialize()
    {
        if (self::$isInitialized) {
            return;
        }

        Module::initialize();

        foreach (self::$initializationCallbacks as $callback) {
            $callback();
        }

        self::$isInitialized = true;
    }

    private static function find($url)
    {
        if (self::$router === null) {
            throw new Server\Exception\ResourceNotFound;
        }

        $results = self::$router->find($url);

        if (!$results) {
            throw new Server\Exception\ResourceNotFound;
        }

        $retval = null;

        for ($i = count($results)-1; $i >= 0; $i--) {

            $resource = $results[$i]["data"]->arguments($results[$i]["arguments"]);

            if ($retval == null) {
                $retval = $resource;
            } else {
                $retval->merge($resource);
            }

        }

        if ($retval->getIsAbstract()) {
            throw new Server\Exception\ResourceNotFound;
        }

        return $retval;
    }

}