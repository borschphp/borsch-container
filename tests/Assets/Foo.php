<?php
/**
 * @author debuss-a
 */

namespace BorschTest\Assets;

class Foo
{
    /** @var Bar */
    public $bar;

    /**
     * @param Bar $bar
     */
    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }
}
