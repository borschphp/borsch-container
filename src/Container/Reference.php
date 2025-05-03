<?php
/**
 * @author debuss-a
 */

namespace Borsch\Container;

/**
 * Class Reference
 * @package Borsch\Container
 */
class Reference
{

    public function __construct(
        protected string $id
    ) {}

    /**
     * Returns the ID of the reference.
     */
    public function references(): string
    {
        return $this->id;
    }
}
