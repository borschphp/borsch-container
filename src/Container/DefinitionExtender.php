<?php

namespace Borsch\Container;

use function call_user_func_array;

class DefinitionExtender extends Definition
{

    public function __construct(
        string $id,
        /** @var callable $callable */
        protected $callable,
        protected string $from,
        bool $cached = false
    ) {
        parent::__construct($id, null, $cached);
    }

    public function get(): mixed
    {
        return call_user_func_array($this->callable, [
            $this->container->get($this->from),
            $this->container
        ]);
    }
}
