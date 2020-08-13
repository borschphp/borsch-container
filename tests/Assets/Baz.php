<?php
/**
 * @author debuss-a
 */

namespace BorschTest\Assets;

class Baz
{

    /** @var array */
    protected $values;

    /**
     * Baz constructor.
     *
     * @param null|array $values
     */
    public function __construct(?array $values = null)
    {
        $this->values = $values ?: [
            'zero',
            'one',
            'two',
            'three'
        ];
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
