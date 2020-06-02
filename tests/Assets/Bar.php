<?php
/**
 * @author debuss-a
 */

namespace BorschTest\Assets;

class Bar
{
    /** @var string */
    public $something;

    /**
     * @param string $something
     */
    public function setSomething(string $something): void
    {
        $this->something = $something;
    }
}
