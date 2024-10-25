<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\RequestCookies;

use Yiisoft\Cookies\RequestCookies\Exception\RequestCookieCollectionNotSetException;

/**
 * Stores request for further consumption by attribute handlers.
 */
final class RequestCookiesProvider implements RequestCookiesProviderInterface
{
    /**
     * @var RequestCookies|null The collection.
     */
    private ?RequestCookies $cookieCollection = null;

    public function set(RequestCookies $cookieCollection): void
    {
        $this->cookieCollection = $cookieCollection;
    }

    public function get(): RequestCookies
    {
        if ($this->cookieCollection === null) {
            throw new RequestCookieCollectionNotSetException();
        }

        return $this->cookieCollection;
    }
}
