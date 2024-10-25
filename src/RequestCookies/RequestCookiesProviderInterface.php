<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\RequestCookies;

use Yiisoft\Cookies\RequestCookies\Exception\RequestCookieCollectionNotSetException;

/**
 * Provides a way to set the current cookie collection and then get it when needed.
 */
interface RequestCookiesProviderInterface
{
    /**
     * Set the current cookie request collection.
     *
     * @param RequestCookies $cookieCollection The collection to set.
     */
    public function set(RequestCookies $cookieCollection): void;

    /**
     * Get the current request cookie collection.
     *
     * @throws RequestCookieCollectionNotSetException
     */
    public function get(): RequestCookies;
}
