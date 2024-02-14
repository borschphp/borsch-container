<?php
/**
 * @author debuss-a
 */

namespace Borsch\Container\Exception;

use Exception;
use Psr\Container\ContainerExceptionInterface;
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
            /** @infection-ignore-all */
            $exception->getCode() ?? 0,
            $exception
        );
    }
}
