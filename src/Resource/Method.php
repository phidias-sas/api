<?php 
namespace Phidias\Api\Resource;

class Method
{
    private $name;
    private $properties;

    public function __construct($name)
    {
        $this->name       = trim(strtolower($name));
        $this->properties = [];
    }

    public function getName()
    {
        return $this->name;
    }

    public function property($propertyName, $propertyValue, $urlPattern = null, $propertyArguments = [])
    {
        if (!isset($this->properties[$propertyName])) {
            $this->properties[$propertyName] = [];
        }

        $this->properties[$propertyName][] = new Property($propertyName, $propertyValue, $urlPattern, $propertyArguments);

        return $this;
    }


    public function getProperty($propertyName, $defaultReturnValue = null)
    {
        return isset($this->properties[$propertyName]) ? $this->properties[$propertyName][0] : $defaultReturnValue;
    }

    public function getAllProperties($propertyName = null)
    {
        if ($propertyName == null) {
            return $this->properties;
        }

        return isset($this->properties[$propertyName]) ? $this->properties[$propertyName] : [];
    }

    public function merge($method)
    {
        foreach ($method->properties as $propertyName => $propertyObjects) {

            if (!isset($this->properties[$propertyName])) {
                $this->properties[$propertyName] = $propertyObjects;
            } else {
                $this->properties[$propertyName] = array_merge($this->properties[$propertyName], $propertyObjects);
            }

        }

        return $this;
    }

    public function setPropertyArguments($arguments)
    {
        foreach ($this->properties as $properties) {
            foreach ($properties as $property) {
                $property->arguments = $arguments;
            }
        }
    }



    /* Common properties */
    public function controller($controller)
    {
        $this->property("controller", $controller);
    }

    public function template($template)
    {
        $this->property("template", $template);
    }

}