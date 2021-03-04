<?php

declare(strict_types=1);

namespace Yiisoft\Cookies;

use RuntimeException;
use Yiisoft\Security\DataIsTamperedException;
use Yiisoft\Security\Mac;

/**
 * A CookieSigner signs the cookie value and checks whether the signed cookie value has been tampered.
 *
 * @see Cookie
 */
final class CookieSigner
{
    private Mac $mac;

    /**
     * @var string The secret key used to sign and validate cookies.
     */
    private string $key;

    /**
     * @param string The secret key used to sign and validate cookies.
     * @param Mac|null $mac If the null, the Mac instance with the default hashing algorithm will be created.
     */
    public function __construct(string $key, Mac $mac = null)
    {
        $this->key = $key;
        $this->mac = $mac ?? new Mac();
    }

    /**
     * Returns a new cookie instance with the signed cookie value.
     *
     * @param Cookie $cookie The cookie with unsigned value.
     *
     * @return Cookie The cookie with signed value.
     */
    public function sign(Cookie $cookie): Cookie
    {
        $value = $this->mac->sign($cookie->getValue(), $this->key);
        return $cookie->withValue($value);
    }

    /**
     * Returns a new cookie instance with the unsigned cookie value.
     *
     * @param Cookie $cookie The cookie with signed value.
     *
     * @throws RuntimeException If the cookie value is tampered. If you are not sure that
     * the value of the cookie file was signed earlier, then first use the {@see validate()} method.
     *
     * @return Cookie The cookie with unsigned value.
     */
    public function unsign(Cookie $cookie): Cookie
    {
        try {
            $value = $this->mac->getMessage($cookie->getValue(), $this->key);
        } catch (DataIsTamperedException $e) {
            throw new RuntimeException("The \"{$cookie->getValue()}\" cookie value was tampered with.");
        }

        return $cookie->withValue($value);
    }

    /**
     * Validates whether the cookie value was tampered with.
     *
     * @param Cookie $cookie The cookie with signed value for validation.
     *
     * @return bool Whether the cookie value was tampered with.
     */
    public function validate(Cookie $cookie): bool
    {
        try {
            $this->mac->getMessage($cookie->getValue(), $this->key);
            return true;
        } catch (DataIsTamperedException $e) {
            return false;
        }
    }
}
