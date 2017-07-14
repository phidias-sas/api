<?php
namespace Phidias\Api\Server;

class Configuration
{
    private static $variables = array();
    private static $watches   = array();

    public static function set($variable, $value = null)
    {
        $changes         = is_array($variable) || is_object($variable) ? (array)$variable : [$variable => $value];
        self::$variables = array_merge(self::$variables, $changes);

        self::triggerWatches($changes);
    }

    public static function get($variable, $defaultValue = null)
    {
        return isset(self::$variables[$variable]) ? self::$variables[$variable] : $defaultValue;
    }

    public static function watch($pattern, $callback)
    {
        $watch = [
            "pattern"  => $pattern,
            "callback" => $callback
        ];

        self::$watches[] = $watch;

        //Trigger the newly created watch on existing configuration settings
        foreach (self::$variables as $variableName => $variableValue) {
            if (self::matchesPattern($variableName, $watch["pattern"])) {
                call_user_func_array($watch["callback"], [$variableValue, $variableName]);
            }
        }
    }

    public static function getAll($prefix = null)
    {
        if ($prefix) {

            $retval = array();
            $len    = strlen($prefix);

            foreach (self::$variables as $name => $value) {
                if (substr($name, 0, $len) == $prefix) {
                    $retval[substr($name,$len)] = $value;
                }
            }

            return $retval;
        }

        return self::$variables;
    }

    public static function getObject($objectName)
    {
        $retval = new \stdClass;

        foreach (self::getAll("$objectName.") as $variableName => $value) {
            self::setObjectProperty($retval, $variableName, $value);
        }

        return $retval;
    }

    private static function setObjectProperty($object, $propertyName, $value)
    {
        $parts    = is_array($propertyName) ? $propertyName : explode('.', $propertyName);
        $property = array_shift($parts);

        if (!count($parts)) {
            $object->$property = $value;

            return;
        }

        if (!isset($object->$property)) {
            $object->$property = new \stdClass;
        }

        self::setObjectProperty($object->$property, $parts, $value);
    }


    private static function triggerWatches($changes)
    {
        foreach ($changes as $variableName => $variableValue) {
            foreach (self::$watches as $watch) {
                if (self::matchesPattern($variableName, $watch["pattern"])) {
                    call_user_func_array($watch["callback"], [$variableValue, $variableName]);
                }
            }
        }
    }

    private static function matchesPattern($name, $pattern)
    {
        if ($pattern === $name) {
            return true;
        }

        if ($pattern[0] === "/") {
            return preg_match($pattern, $name);
        }

        return false;
    }

}
