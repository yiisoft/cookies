<?php

namespace Yiisoft\Cookies;

use Yiisoft\Cookies\Tests\CookieTest;

function time(): int
{
    return CookieTest::$timeResult ?? \time();
}
