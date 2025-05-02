<?php

namespace Borsch\Container;

class Reference
{

    public function __construct(
        protected string $id
    ) {}

    public function reference(): string
    {
        return $this->id;
    }
}
