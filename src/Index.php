<?php 
namespace Phidias\Api;

use Phidias\Api\Index\Result;

class Index
{
    private $identifier;

    private $data;

    private $literalNodes;
    private $attributeNodes;
    private $catchAllNode;

    public function __construct($identifier = null)
    {
        $this->identifier     = $identifier;
        $this->data           = [];

        $this->literalNodes   = [];
        $this->attributeNodes = [];
        $this->catchAllNode   = null;
    }

    public function store($path, $data)
    {
        $path       = trim($path, " \t./");
        $targetNode = $this->getNode($path);

        $targetNode->addData($data);

        return $this;
    }

    public function find($path)
    {
        $path   = trim($path, " \t./");
        $crumbs = explode("/", $path);

        $results = [];
        $this->retrieve($crumbs, $results);

        return $results;
    }

    private function addData($data)
    {
        $this->data[] = $data;
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
                $this->catchAllNode = new Index;
            }

            return $this->catchAllNode;
        }

        if (substr($crumb, 0, 1) === "{") {

            $identifier = substr($crumb, 1, -1);

            if (!isset($this->attributeNodes[$identifier])) {
                $this->attributeNodes[$identifier] = new Index($identifier);
            }

            return $this->attributeNodes[$identifier];

        }

        $identifier = $crumb;

        if (!isset($this->literalNodes[$identifier])) {
            $this->literalNodes[$identifier] = new Index($identifier);
        }

        return $this->literalNodes[$identifier];
    }


    private function retrieve(array $crumbs, &$results = [], $attributes = [])
    {
        if (count($crumbs) === 0) {
            foreach ($this->data as $data) {
                $results[] = new Result($data, $attributes);
            }
            return;
        }

        $crumb = trim(array_shift($crumbs));

        if (isset($this->literalNodes[$crumb])) {
            $this->literalNodes[$crumb]->retrieve($crumbs, $results, $attributes);
        }

        foreach ($this->attributeNodes as $attributeNode) {

            $branchAttributes = $attributes;

            if (substr($attributeNode->identifier, -1) !== "*") {
                $branchAttributes[$attributeNode->identifier] = $crumb;
                $attributeNode->retrieve($crumbs, $results, $branchAttributes);
            }
        }

        foreach ($this->attributeNodes as $attributeNode) {

            $branchAttributes = $attributes;

            if (substr($attributeNode->identifier, -1) === "*") {

                $fullPath                         = $crumb . ($crumbs ? "/".implode("/", $crumbs) : null);
                $attributeName                    = substr($attributeNode->identifier, 0, -1);
                $branchAttributes[$attributeName] = $fullPath;

                foreach ($attributeNode->data as $data) {
                    $results[] = new Result($data, $branchAttributes);
                }

            }

        }

        if ($this->catchAllNode !== null) {
            foreach ($this->catchAllNode->data as $data) {
                $results[] = new Result($data, $attributes);
            }
        }
    }

}