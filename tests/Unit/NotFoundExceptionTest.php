<?php

use Borsch\Container\Exception\NotFoundException;

test('unableToFindEntry() returns a NotFoundException', function () {
    $id = substr(md5(mt_rand()), 0, 7);
    $exception = NotFoundException::unableToFindEntry($id);
    expect($exception)->toBeInstanceOf(NotFoundException::class)
        ->and($exception->getMessage())->toBe(sprintf(
            'Unable to find entry with ID "%s".',
            $id
        ))
        ->and($exception->getCode())->toBe(0)
        ->and($exception->getPrevious())->toBeNull();
});
