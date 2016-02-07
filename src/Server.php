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

        $resource = Resource::factory($resource);

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



    private static function getIndex()
    {
        if (self::$index === null) {
            self::$index = new Index;
        }

        return self::$index;
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

        $method = strtoupper($request->getMethod());
        $path   = urldecode($request->getUri()->getPath());

        Debug::startBlock("$method $path", "resource");

        try {

            $dispatcher = self::getDispatcher($request);
            $response   = $dispatcher->dispatch($request);

        } catch (Server\Exception\ResourceNotFound $e) {

            $response = new Response(404);

        } catch (Server\Exception\MethodNotImplemented $e) {

            $response = (new Response(405))->withHeader("Allowed", $e->getImplementedMethods());

        }

        Debug::endBlock();

        return $response;
    }


    /**
     * Look in the index for all actions matching the request
     * and create a new dispatcher from them.
     */
    private static function getDispatcher($request)
    {
        $dispatcher = new Dispatcher;

        $method  = $request->getMethod();
        $url     = urldecode($request->getUri()->getPath());

        $results = self::getIndex()->find($url);

        if (!$results) {
            throw new Server\Exception\ResourceNotFound;
        }

        // Abstract resources should only be included alongside NON abstract resources
        $nonAbstractMethodsArePresent = false;

        foreach ($results as $result) {
            if (!$result->data->getIsAbstract()) {
                $nonAbstractMethodsArePresent = true;
                break;
            }
        }

        if (!$nonAbstractMethodsArePresent) {
            throw new Server\Exception\ResourceNotFound;
        }


        $foundMethod        = false;
        $implementedMethods = [];

        foreach ($results as $result) {

            $resource           = $result->data;
            $attributes         = $result->attributes;
            $implementedMethods = array_merge($implementedMethods, $resource->getImplementedMethods());


            foreach ($resource->getActions($method) as $methodAction) {

                $action = Action::factory($methodAction);

                if (count($action->getControllers())) {
                    $foundMethod = true;
                }

                $dispatcher->add($action, $attributes);
            }

        }


        if (!$foundMethod) {
            throw new Server\Exception\MethodNotImplemented($implementedMethods);
        }

        return $dispatcher;
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