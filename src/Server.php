<?php
namespace Phidias\Api;

use Phidias\Utilities\Debugger as Debug;

use Phidias\Api\Server\Module;

use Phidias\Api\Http\ServerRequest;
use Phidias\Api\Http\Response;
use Phidias\Api\Http\Stream;

class Server
{
    private static $index;
    private static $isInitialized;

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

        self::getIndex()->store($path, $resource);
        return $resource;
    }

    /**
     * Construct an Action
     *
     * Server::resource("/hello")
     *     ->on("get", Server::action()
     *         ->controller("myController")
     *     );
     *
     */
    public static function action($actionData = null)
    {
        return Action::factory($actionData);
    }

    /**
     */
    public static function access($cors = null)
    {
        return AccessControl::factory($cors);
    }


    /**
     * Obtain the current request from the environment, execute it and relay the response
     *
     */
    public static function run()
    {
        $request  = Environment::getServerRequest();
        $response = self::execute($request);
        Environment::sendResponse($response);
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

        $method = $request->getMethod();
        $path   = $request->getUri()->getPath();
        Debug::startBlock("$method $path", "resource");

        try {

            $resource   = self::getResource($path);
            $dispatcher = $resource->getDispatcher($method);
            $response   = $dispatcher->dispatch($request);

        } catch (Server\Exception\ResourceNotFound $e) {

            $response = new Response(404);

        } catch (Server\Exception\MethodNotImplemented $e) {

            if ($method == "options") {
                $response      = new Response(200);
                $accessControl = $resource->getAccessControl()->allowMethods($e->getImplementedMethods());
                $response      = $accessControl->filter($response, $request);
            } else {
                $response = (new Response(405))->withHeader("Allowed", $e->getImplementedMethods());
            }

        }

        Debug::endBlock();

        return $response;
    }

    public static function getResource($path)
    {
        $results = self::getIndex()->find($path);
        if (!$results) {
            throw new Server\Exception\ResourceNotFound;
        }

        $retval = null;
        foreach ($results as $result) {
            $resource = Resource::factory($result->data);
            $resource->setAttributes($result->attributes);
            $retval = $retval ? $retval->merge($resource) : $resource;
        }

        if ($retval->getIsAbstract()) {
            throw new Server\Exception\ResourceNotFound;
        }

        return $retval;
    }

    private static function getIndex()
    {
        if (self::$index === null) {
            self::$index = new Index;
        }

        return self::$index;
    }

    private static function initialize()
    {
        if (self::$isInitialized) {
            return;
        }

        Module::initialize();

        self::$isInitialized = true;
    }

}