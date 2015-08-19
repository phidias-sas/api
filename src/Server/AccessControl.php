<?php
namespace Phidias\Api\Server;

class AccessControl
{
    private $allowOrigin;
    private $allowCredentials;
    private $allowHeaders;
    private $allowMethods;
    private $exposeHeaders;

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

    public function allowHeaders($allowHeaders)
    {
        $this->allowHeaders = $allowHeaders;
        return $this;
    }

    public function allowMethods($allowMethods)
    {
        $this->allowMethods = $allowMethods;
        return $this;
    }

    public function exposeHeaders($exposeHeaders)
    {
        $this->exposeHeaders = $exposeHeaders;
        return $this;
    }

    public function allowAll()
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
        if ($this->allowOrigin === "*") {
            $this->allowOrigin = ($origin = $request->getHeader("origin")[0]) ? $origin : "*";
        }

        return $response
            ->header("Access-Control-Allow-Origin",      $this->allowOrigin)
            ->header("Access-Control-Allow-Credentials", $this->allowCredentials)
            ->header("Access-Control-Allow-Headers",     $this->allowHeaders)
            ->header("Access-Control-Allow-Methods",     $this->allowMethods)
            ->header("Access-Control-Expose-Headers",    $this->exposeHeaders);
    }
}