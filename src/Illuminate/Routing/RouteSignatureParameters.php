<?php

namespace Illuminate\Routing;

use Illuminate\Support\Reflector;
use Illuminate\Support\Str;
use ReflectionFunction;
use ReflectionMethod;

class RouteSignatureParameters
{
    /**
     * Extract the route action's signature parameters.
     *
     * @param  array  $action
     * @param  array  $conditions
     * @return array
     */
    public static function fromAction(array $action, $conditions = [])
    {
        $callback = RouteAction::containsSerializedClosure($action)
                        ? unserialize($action['uses'])->getClosure()
                        : $action['uses'];

        $parameters = is_string($callback)
                        ? static::fromClassMethodString($callback)
                        : (new ReflectionFunction($callback))->getParameters();

        switch (true) {
            case ! empty($conditions['subClass']):
                return array_filter($parameters, function ($p) use ($conditions) {
                    return Reflector::isParameterSubclassOf($p, $conditions['subClass']);
                });
            case ! empty($conditions['backedEnum']):
                return array_filter($parameters, function ($p) {
                    return Reflector::isParameterBackedEnumWithStringBackingType($p);
                });
            default:
                return $parameters;
        }
    }

    /**
     * Get the parameters for the given class / method by string.
     *
     * @param  string  $uses
     * @return array
     */
    protected static function fromClassMethodString($uses)
    {
        [$class, $method] = Str::parseCallback($uses);

        if (! method_exists($class, $method) && Reflector::isCallable($class, $method)) {
            return [];
        }

        return (new ReflectionMethod($class, $method))->getParameters();
    }
}
