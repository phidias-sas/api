<?php
namespace Phidias\Api\Resource;

/**
 * Resource Controller superclass
 * 
 * Although you are encouraged to separate your application
 * logic from resource representations and HTTP interactions
 * some cases justify direct access to Request and Resource
 * attributes from the controller.
 * 
 * This class serves as a superclass you can extend, and will
 * provide access to the current request and response, as well
 * as request attributes and parameters.
 * 
 * e.g.:
 * 
 * The resource declaration:
 * 
 * Server::resource("/hello", [
 *     "get" => [
 *         "controller" => "myClass->getHello()"
 *     ]
 * ]);
 * 
 * 
 * myClass:
 * 
 * class myClass extends Phidias\Api\Resource\Controller
 * {
 *     public function getHello()
 *     {
 *         echo $this->request->getHeader("Host");
 *         echo $this->parameters->get("limit", 10);
 *         echo $this->arguments->get("argumentName");
 * 
 *         $this->response->status(200);
 *     }
 * }
 * 
 */
class Controller
{
    protected $request;
    protected $response;

    protected $parameters;  //Query string parameters
    protected $attributes;  //Inferred URL attributes  (e.g.: people/{personId}/stuff   $this->attributes->get("personId"))

    public function __construct($request, $response)
    {
        $this->request  = $request;
        $this->response = $response;

        $this->parameters = new Attributes($request->getQueryParams());
        $this->attributes = new Attributes($request->getAttributes());
    }
}