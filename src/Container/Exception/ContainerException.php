<?php declare(strict_types=1);
/**
 * @author debuss-a
 */

namespace Borsch\Container\Exception;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use ReflectionType;
use function sprintf, method_exists;

/**
 * Class ContainerException
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{

    public static function unableToGetCallableParameter(ReflectionType $type, string $id, ?Exception $exception = null) :self
    {
        return new self(
            sprintf(
                'Unable to get parameter for callable/closure defined in entry with ID "%s". Expected a parameter of type "%s" but could not be found inside the container nor its delegates.',
                $id,
                method_exists($type, 'getName') ? $type->getName() : 'Unknown type'
            ),
            $exception?->getCode() ?? 0,
            $exception
        );
    }

    public static function unableToGetClassReflection(string $classname, ?ReflectionException $exception = null) :self
    {
        return new self(
            sprintf(
                'Unable to create a reflection for class "%s", it does not exist.',
                $classname
            ),
            $exception?->getCode() ?? 0,
            $exception
        );
    }

    public static function unableToGetFunctionReflection(string $function, ?ReflectionException $exception = null) :self
    {
        return new self(
            sprintf(
                'Unable to create a reflection for function "%s", it does not exist.',
                $function
            ),
            $exception?->getCode() ?? 0,
            $exception
        );
    }

    public static function extendingWithSameIdAndFromForbidden(string $id): self
    {
        return new self(
            sprintf(
                'It is forbidden to extend a definition with the same ID (%s), provide a new ID (e.g. $from) to fix the issue.',
                $id
            )
        );
    }

    public static function extendingAnExistingEntryIsForbidden(string $id): self
    {
        return new self(
            sprintf(
                'It is forbidden to extend a definition and register it with an existing ID (%s), provide a new ID (e.g. $from) to fix the issue.',
                $id
            )
        );
    }
}
