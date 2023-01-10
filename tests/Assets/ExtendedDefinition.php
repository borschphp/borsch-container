<?php

namespace BorschTest\Assets;

use Borsch\Container\Definition;

class ExtendedDefinition extends Definition
{
    public function getId(): string
    {
        return $this->id;
    }

    public function getConcrete(): mixed
    {
        return $this->concrete;
    }
}
