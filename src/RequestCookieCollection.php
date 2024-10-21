<?php

declare(strict_types=1);

namespace Yiisoft\Cookies;

use InvalidArgumentException;

/**
 * A CookieCollection helps to work with many cookies at once and to read request cookies.
 *
 * @see Cookie
 *
 */
final class RequestCookieCollection
{
    /**
     * @var Cookie[] The cookies in this collection (indexed by the cookie name).
     *
     * @psalm-var array<string, Cookie>
     */
    private array $cookies = [];

    /**
     * CookieCollection constructor.
     *
     * @param array|Cookie[] $cookies The cookies that this collection initially contains.
     */
    public function __construct(array $cookies = [])
    {
        foreach ($cookies as $cookie) {
            if (!($cookie instanceof Cookie)) {
                throw new InvalidArgumentException('CookieCollection can contain only Cookie instances.');
            }

            $this->cookies[$cookie->getName()] = $cookie;
        }
    }

    /**
     * Returns the value of the named cookie.
     *
     * @param string $name The cookie name.
     * @param string|null $defaultValue The value that should be returned when the named cookie does not exist.
     *
     * @return string|null The value of the named cookie or the default value if cookie is not set.
     *
     * @see get()
     */
    public function getValue(string $name, ?string $defaultValue = null): ?string
    {
        return isset($this->cookies[$name]) ? $this->cookies[$name]->getValue() : $defaultValue;
    }

    /**
     * Returns the cookie with the specified name.
     *
     * @param string $name The cookie name.
     *
     * @return Cookie|null The cookie with the specified name. Null if the named cookie does not exist.
     *
     * @see getValue()
     */
    public function get(string $name): ?Cookie
    {
        return $this->cookies[$name] ?? null;
    }

    /**
     * Returns whether there is a cookie with the specified name.
     *
     * @param string $name The cookie name.
     *
     * @return bool Whether the named cookie exists.
     *
     * @see remove()
     */
    public function has(string $name): bool
    {
        return isset($this->cookies[$name]);
    }
}
