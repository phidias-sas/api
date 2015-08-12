<?php 
namespace Phidias\Api;

class Router
{
    private $identifier;

    private $data;

    private $literalNodes;
    private $argumentNodes;
    private $catchAllNode;

    public function __construct($identifier = null)
    {
        $this->identifier    = $identifier;
        $this->data          = null;

        $this->literalNodes  = [];
        $this->argumentNodes = [];
        $this->catchAllNode  = null;
    }

    public function store($path, $data)
    {
        $path = trim($path, " \t./");
        $targetNode = $this->getNode($path)->setData($data);
    }

    public function find($path)
    {
        $path   = trim($path, " \t./");
        $crumbs = explode("/", $path);

        $results = [];
        $this->retrieve($crumbs, $results);

        return $results;
    }

    private function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    private function getNode($path)
    {
        $targetNode = $this;
        $crumbs     = explode("/", $path);

        foreach ($crumbs as $crumb) {
            $targetNode = $targetNode->getChildNode($crumb);
        }

        return $targetNode;
    }

    private function getChildNode($crumb)
    {
        if ($crumb === "*") {

            if ($this->catchAllNode === null) {
                $this->catchAllNode = new Router;
            }

            return $this->catchAllNode;
        }

        if (substr($crumb, 0, 1) === "{") {

            $identifier = substr($crumb, 1, -1);

            if (!isset($this->argumentNodes[$identifier])) {
                $this->argumentNodes[$identifier] = new Router($identifier);
            }

            return $this->argumentNodes[$identifier];

        }

        $identifier = $crumb;

        if (!isset($this->literalNodes[$identifier])) {
            $this->literalNodes[$identifier] = new Router($identifier);
        }

        return $this->literalNodes[$identifier];
    }

    private function retrieve(array $crumbs, &$results = [], $arguments = [])
    {
        if (count($crumbs) === 0) {
            if ($this->data !== null) {
                $results[] = [
                    "data"      => $this->data,
                    "arguments" => $arguments
                ];
            }
            return;
        }

        $crumb = trim(array_shift($crumbs));

        if (isset($this->literalNodes[$crumb])) {
            $this->literalNodes[$crumb]->retrieve($crumbs, $results, $arguments);
        }

        foreach ($this->argumentNodes as $argumentNode) {

            $branchArguments = $arguments;

            if (substr($argumentNode->identifier, -1) !== "*") {
                $branchArguments[$argumentNode->identifier] = $crumb;
                $argumentNode->retrieve($crumbs, $results, $branchArguments);
            }
        }

        foreach ($this->argumentNodes as $argumentNode) {

            $branchArguments = $arguments;

            if (substr($argumentNode->identifier, -1) === "*") {

                $fullPath                       = $crumb . ($crumbs ? "/".implode("/", $crumbs) : null);
                $argumentName                   = substr($argumentNode->identifier, 0, -1);
                $branchArguments[$argumentName] = $fullPath;

                $results[] = [
                    "data"      => $argumentNode->data,
                    "arguments" => $branchArguments
                ];

            }

        }

        if ($this->catchAllNode !== null) {
            $results[] = [
                "data"      => $this->catchAllNode->data,
                "arguments" => $arguments
            ];
        }
    }

}