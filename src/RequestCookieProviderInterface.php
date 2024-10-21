<?php

declare(strict_types=1);

namespace Yiisoft\Cookies;

use Yiisoft\Cookies\Exception\RequestCookieCollectionNotSetException;

/**
 * Provides a way to set the current cookie collection and then get it when needed.
 */
interface RequestCookieProviderInterface
{
    /**
     * Set the current cookie request collection.
     *
     * @param RequestCookieCollection $cookieCollection The collection to set.
     */
    public function set(RequestCookieCollection $cookieCollection): void;

    /**
     * Get the current request cookie collection.
     *
     * @throws RequestCookieCollectionNotSetException
     */
    public function get(): RequestCookieCollection;
}
