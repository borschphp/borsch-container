<?php
/**
 * @author debuss-a
 */

namespace Borsch\Container;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class NotFoundException
 * @package Borsch\Container
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface {}
