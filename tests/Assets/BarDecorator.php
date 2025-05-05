<?php

namespace BorschTest\Assets;

class BarDecorator
{

    public function __construct(
        public Bar $bar
    ) {}

    public function getBar(): Bar
    {
        return $this->bar;
    }
}