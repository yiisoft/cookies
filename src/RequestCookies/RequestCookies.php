<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\RequestCookies;

/**
 * A RequestCookies helps to work with many cookies at once and to read request cookies.
 */
final class RequestCookies
{
    /**
     * @var array The cookies in this collection (indexed by the cookie name).
     *
     * @psalm-var array<string, string>
     */
    private array $cookies = [];

    /**
     * RequestCookies constructor.
     *
     * @param array<string, string> $cookies The cookies that this collection initially contains.
     */
    public function __construct(array $cookies = [])
    {
        foreach ($cookies as $name => $value) {
            $this->cookies[$name] = $value;
        }
    }

    /**
     * Returns the cookie with the specified name.
     *
     * @param string $name The cookie name.
     *
     * @return ?string The cookie with the specified name. Null if the named cookie does not exist.
     *
     * @see getValue()
     */
    public function get(string $name): ?string
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
