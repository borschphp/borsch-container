<?php

use Borsch\Container\Reference;

covers(Reference::class);

test('reference() returns the id', function () {
    $reference = new Reference('test');
    expect($reference->reference())->toBe('test');
});
