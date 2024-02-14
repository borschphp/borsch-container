<?php

use Borsch\Container\Container;

/*
|--------------------------------------------------------------------------
| Reusable (Shared) Setup and Teardown
|--------------------------------------------------------------------------
|
| At some point, you may need (or want) to share some kind of test scenario
| setup or teardown procedure.
| https://pestphp.com/docs/setup-and-teardown#reusable-shared-setup-and-teardown
*/

uses()
    ->beforeEach(function () {
        $this->container = new Container();
    })
    ->in('Unit');
