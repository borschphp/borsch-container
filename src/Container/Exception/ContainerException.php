<?php
/**
 * @author debuss-a
 */

namespace Borsch\Container\Exception;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * Class ContainerException
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{

    public static function unableToGetCallableParameter(
        ReflectionNamedType|ReflectionUnionType $type,
        string $id,
        ?Exception $exception = null
    ) :static
    {
        return new static(
            sprintf(
                'Unable to get parameter for callable/closure defined in entry with ID "%s". '.
                'Expected a parameter of type "%s" but could not be found inside the container nor its delegates.',
                $id,
                $type->getName()
            ),
            $exception->getCode() ?? 0,
            $exception
        );
    }

    public static function unableToGetClassReflection(string $classname, ReflectionException $exception = null) :static
    {
        return new static(
            sprintf(
                'Unable to create a reflection for class "%s", it does not exist.',
                $classname
            ),
            $exception->getCode() ?? 0,
            $exception
        );
    }

    public static function unableToGetFunctionReflection(string $function, ReflectionException $exception = null) :static
    {
        return new static(
            sprintf(
                'Unable to create a reflection for function "%s", it does not exist.',
                $function
            ),
            $exception->getCode() ?? 0,
            $exception
        );
    }
}
