<?php
namespace Phidias\Api;

class AccessControl
{
    private $allowOrigin;
    private $allowCredentials;
    private $allowHeaders;
    private $allowMethods;
    private $exposeHeaders;


    public function filter($response, $request = null)
    {
        $origin = ($this->allowOrigin === "*" && $request !== null && $request->hasHeader("origin")) ? $request->getHeader("origin") : $this->allowOrigin;

        return $response
            ->header("Access-Control-Allow-Origin",      $origin)
            ->header("Access-Control-Allow-Credentials", $this->allowCredentials)
            ->header("Access-Control-Allow-Headers",     $this->allowHeaders)
            ->header("Access-Control-Allow-Methods",     $this->allowMethods)
            ->header("Access-Control-Expose-Headers",    $this->exposeHeaders);
    }


    public static function factory($data)
    {
        if (is_a($data, "Phidias\Api\AccessControl")) {
            return $data;
        }

        $retval = new AccessControl;

        if ($data === "full") {
            return $retval->allowFull();
        }

        if (!is_array($data)) {
            return $retval;
        }

        if (isset($data["allow-origin"])) {
            $retval->allowOrigin($data["allow-origin"]);
        }

        if (isset($data["allow-credentials"])) {
            $retval->allowCredentials($data["allow-credentials"]);
        }

        if (isset($data["allow-headers"])) {
            $retval->allowHeaders((array)$data["allow-headers"]);
        }

        if (isset($data["allow-methods"])) {
            $retval->allowMethods((array)$data["allow-methods"]);
        }

        if (isset($data["expose-headers"])) {
            $retval->exposeHeaders((array)$data["expose-headers"]);
        }

        return $retval;
    }

    public function allowOrigin($allowOrigin)
    {
        $this->allowOrigin = $allowOrigin;
        return $this;
    }

    public function allowCredentials($allowCredentials = true)
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
            ->exposeHeaders(["Location"]);
    }

}