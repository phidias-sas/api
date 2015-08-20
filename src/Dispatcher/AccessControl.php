<?php
namespace Phidias\Api\Dispatcher;

class AccessControl
{
    private $allowOrigin;
    private $allowCredentials;
    private $allowHeaders;
    private $allowMethods;
    private $exposeHeaders;

    public static function factory($array)
    {
        $retval = new AccessControl;

        if ($array === "full") {
            return $retval->allowFull();
        }

        if (isset($array["allow-origin"])) {
            $retval->allowOrigin($array["allow-origin"]);
        }

        if (isset($array["allow-credentials"])) {
            $retval->allowCredentials($array["allow-credentials"]);
        }

        if (isset($array["allow-headers"])) {
            $retval->allowHeaders((array)$array["allow-headers"]);
        }

        if (isset($array["allow-methods"])) {
            $retval->allowMethods((array)$array["allow-methods"]);
        }

        if (isset($array["expose-headers"])) {
            $retval->exposeHeaders((array)$array["expose-headers"]);
        }

        return $retval;
    }

    public function allowOrigin($allowOrigin)
    {
        $this->allowOrigin = $allowOrigin;
        return $this;
    }

    public function allowCredentials($allowCredentials)
    {
        $this->allowCredentials = $allowCredentials;
        return $this;
    }

    public function allowHeaders(array $allowHeaders)
    {
        $this->allowHeaders = $allowHeaders;
        return $this;
    }

    public function allowMethods(array $allowMethods)
    {
        $this->allowMethods = $allowMethods;
        return $this;
    }

    public function exposeHeaders(array $exposeHeaders)
    {
        $this->exposeHeaders = $exposeHeaders;
        return $this;
    }

    public function allowFull()
    {
        return $this
            ->allowOrigin("*")
            ->allowCredentials(true)
            ->allowHeaders(["Origin", "X-Requested-With", "Content-Type", "Accept", "Authorization"])
            ->allowMethods(["GET", "POST", "PUT", "DELETE", "OPTIONS"])
            ->exposeHeaders(["Location", "X-Phidias-Collection-Page", "X-Phidias-Collection-Page-Size", "X-Phidias-Collection-Total"]);
    }

    public function filter($response, $request)
    {
        $origin = ($this->allowOrigin === "*" && $request->hasHeader("origin")) ? $request->getHeader("origin") : $this->allowOrigin;

        return $response
            ->header("Access-Control-Allow-Origin",      $origin)
            ->header("Access-Control-Allow-Credentials", $this->allowCredentials)
            ->header("Access-Control-Allow-Headers",     $this->allowHeaders)
            ->header("Access-Control-Allow-Methods",     $this->allowMethods)
            ->header("Access-Control-Expose-Headers",    $this->exposeHeaders);
    }
}