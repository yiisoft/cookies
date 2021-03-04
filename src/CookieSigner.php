<?php

declare(strict_types=1);

namespace Yiisoft\Cookies;

use RuntimeException;
use Yiisoft\Security\DataIsTamperedException;
use Yiisoft\Security\Mac;

use function preg_match;
use function strlen;
use function strpos;
use function substr;

/**
 * A CookieSigner signs the cookie value and validates whether the signed cookie value has been tampered.
 *
 * @see Cookie
 */
final class CookieSigner
{
    /**
     * The signature separator and the cookie value.
     */
    private const SEPARATOR = '__';

    /**
     * @var Mac The Mac instance.
     */
    private Mac $mac;

    /**
     * @var string The secret key used to sign and validate cookies.
     */
    private string $key;

    /**
     * @var int|null The length of the generated signature is determined automatically.
     */
    private ?int $signatureLength = null;

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
        $value = $this->mac->sign(self::SEPARATOR . $cookie->getValue(), $this->key);
        return $cookie->withValue($value);
    }

    /**
     * Returns a new cookie instance with the clean cookie value or throws an exception if signature is not valid.
     *
     * @param Cookie $cookie The cookie with signed value.
     *
     * @throws RuntimeException If the cookie value is tampered. If you are not sure that
     * the value of the cookie file was signed earlier, then first use the {@see isSigned()}.
     *
     * @return Cookie The cookie with unsigned value.
     */
    public function validate(Cookie $cookie): Cookie
    {
        try {
            $value = $this->mac->getMessage($cookie->getValue(), $this->key);
        } catch (DataIsTamperedException $e) {
            throw new RuntimeException("The \"{$cookie->getName()}\" cookie value was tampered with.");
        }

        return $cookie->withValue(substr($value, strlen(self::SEPARATOR)));
    }

    /**
     * Checks whether the cookie value is signed.
     *
     * @param Cookie $cookie The cookie to check.
     *
     * @return bool Whether the cookie value is signed.
     */
    public function isSigned(Cookie $cookie): bool
    {
        if (!$separatorPosition = strpos($cookie->getValue(), self::SEPARATOR)) {
            return false;
        }

        if ($this->signatureLength === null) {
            $this->signatureLength = strlen($this->mac->sign('', ''));
        }

        $signature = substr($cookie->getValue(), 0, $separatorPosition);
        return $this->signatureLength === strlen($signature) && preg_match('/^[0-9a-f]+$/', $signature);
    }
}
