<?php

declare(strict_types=1);

namespace Yiisoft\Cookies;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Yiisoft\Strings\WildcardPattern;

use function is_string;

final class CookieStorage
{
    private CookieCollection $cookies;
    private CookieEncryptor $encryptor;
    private CookieSigner $signer;

    /**
     * @var string[] The names of the cookies that need to be encrypted/decrypted.
     */
    private array $encryption;

    /**
     * @var string[] The names of the cookies that need to be signed/validated.
     */
    private array $signature;

    /**
     * @param string $key The secret key used to encrypt and decrypt cookie values.
     * @param string[] $encryption The names of the cookies that need to be encrypted/decrypted.
     * @param string[] $signature The names of the cookies that need to be signed/validated.
     */
    public function __construct(string $key, array $encryption = [], array $signature = [])
    {
        $this->cookies = new CookieCollection();
        $this->encryptor = new CookieEncryptor($key);
        $this->signer = new CookieSigner($key);
        $this->encryption = $encryption;
        $this->signature = $signature;
    }

    public function add(Cookie $cookie): void
    {
        if ($this->isSecurity($cookie->getName(), $this->encryption)) {
            $cookie = $this->encryptor->encrypt($cookie);
        }

        if ($this->isSecurity($cookie->getName(), $this->signature)) {
            $cookie = $this->signer->sign($cookie);
        }

        $this->cookies->add($cookie);
    }

    public function addToRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $cookieParams = [];

        foreach ($request->getCookieParams() as $name => $value) {
            if (!is_string($name) || !is_string($value)) {
                continue;
            }

            try {
                if ($this->isSecurity($name, $this->encryption)) {
                    $cookie = $this->encryptor->decrypt(new Cookie($name, $value));
                    $cookieParams[$cookie->getName()] = $cookie->getValue();
                    continue;
                }

                if ($this->isSecurity($name, $this->signature)) {
                    $cookie = $this->signer->validate(new Cookie($name, $value));
                    $cookieParams[$cookie->getName()] = $cookie->getValue();
                    continue;
                }
            } catch (RuntimeException $e) {
                // Do need to log something in this case?
                continue;
            }

            $cookieParams[$name] = $value;
        }

        return $request->withCookieParams($cookieParams);
    }

    public function addToResponse(ResponseInterface $response): ResponseInterface
    {
        return $this->cookies->addToResponse($response);
    }

    /**
     * @param string $name
     * @param string[] $patterns
     *
     * @return bool
     */
    public function isSecurity(string $name, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            $wildcard = new WildcardPattern($pattern);

            if ($wildcard->match($name)) {
                return true;
            }
        }

        return false;
    }
}
