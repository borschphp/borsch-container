<?php
/**
 * @author debuss-a
 */

namespace Borsch\Container\Exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class NotFoundException
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{

    public static function unableToFindEntry(string $id): static
    {
        return new static(sprintf('Unable to find entry with ID "%s".', $id));
    }
}
