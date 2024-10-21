<?php

declare(strict_types=1);

namespace Yiisoft\Cookies;

use Yiisoft\Cookies\Exception\RequestCookieCollectionNotSetException;

/**
 * Stores request for further consumption by attribute handlers.
 */
final class RequestCookieProvider implements RequestCookieProviderInterface
{
    /**
     * @var RequestCookieCollection|null The collection.
     */
    private ?RequestCookieCollection $cookieCollection = null;

    public function set(RequestCookieCollection $cookieCollection): void
    {
        $this->cookieCollection = $cookieCollection;
    }

    public function get(): RequestCookieCollection
    {
        if ($this->cookieCollection === null) {
            throw new RequestCookieCollectionNotSetException();
        }

        return $this->cookieCollection;
    }
}
