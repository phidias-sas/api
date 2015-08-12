<?php
namespace Phidias\Api\Resource;

/**
 * Simple name-value-pair wrapper
 */

class Attributes
{
    private $variables;

    public function __construct($variables = [])
    {
        $this->variables = (array)$variables;
    }

    public function set($variableName, $variableValue = null)
    {
        if (is_array($variableName)) {
            $this->variables = array_merge($this->variables, $variableName);
        } else {
            $this->variables[$variableName] = $variableValue;
        }

        return $this;
    }

    public function get($variableName = null, $defaultValue = null)
    {
        if ($variableName === null) {
            return $this->all();
        }

        return isset($this->variables[$variableName]) ? $this->variables[$variableName] : $defaultValue;
    }

    public function except($variableName)
    {
        $retval = clone($this->variables);
        unset($retval[$variableName]);

        return $retval;
    }

    public function all()
    {
        return $this->variables;
    }

    public function has($variableName)
    {
        return isset($this->variables[$variableName]);
    }

    public function required($variableName)
    {
        if (!isset($this->variables[$variableName])) {
            throw new HashTable\Exception\RequiredVariable(array('variable' => $variableName));
        }

        return $this->variables[$variableName];
    }
}
