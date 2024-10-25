<?php

declare(strict_types=1);

/** @var array $params */

use Yiisoft\Cookies\RequestCookies\RequestCookiesProvider;
use Yiisoft\Cookies\RequestCookies\RequestCookiesProviderInterface;

return [
    RequestCookiesProviderInterface::class => [
        'class' => RequestCookiesProvider::class,
    ],
];
