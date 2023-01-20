<?php
/**
 * @author debuss-a
 */

namespace Borsch\Container\Exception;

use Exception;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * Class ContainerException
 */
class ContainerException extends Exception implements \Psr\Container\ContainerExceptionInterface
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
            $exception ? $exception->getCode() : 0,
            $exception
        );
    }
}
