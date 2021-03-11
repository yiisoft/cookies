<?php

declare(strict_types=1);

namespace Yiisoft\Cookies;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

use function in_array;

final class CookieManager
{
    private CookieEncryptor $encryptor;
    private CookieSigner $signer;
    private CookieCollection $cookies;
    private CookieCollection $loaded;

    /**
     * @var string[] The names of the cookies that need to be encrypted/decrypted.
     */
    private array $encryption;

    /**
     * @var string[] The names of the cookies that need to be signed/validated.
     */
    private array $signature;

    /**
     * @var array<string, string> The names of the tampered cookies as keys and error messages as values.
     */
    private array $tampered = [];

    /**
     * @param string $key The secret key used to encrypt and decrypt cookie values.
     * @param string[] $encryption The names of the cookies that need to be encrypted/decrypted.
     * @param string[] $signature The names of the cookies that need to be signed/validated.
     */
    public function __construct(string $key, array $encryption = [], array $signature = [])
    {
        $this->cookies = new CookieCollection();
        $this->loaded = new CookieCollection();
        $this->encryptor = new CookieEncryptor($key);
        $this->signer = new CookieSigner($key);
        $this->encryption = $encryption;
        $this->signature = $signature;
    }

    public function add(Cookie $cookie): void
    {
        if (in_array($cookie->getName(), $this->encryption, true)) {
            $cookie = $this->encryptor->encrypt($cookie);
        }

        if (in_array($cookie->getName(), $this->signature, true)) {
            $cookie = $this->signer->sign($cookie);
        }

        $this->cookies->add($cookie);
    }

    public function addToResponse(ResponseInterface $response): ResponseInterface
    {
        return $this->cookies->addToResponse($response);
    }

    public function load(string $name): ?Cookie
    {
        return $this->loaded->get($name);
    }

    public function loadFromRequest(ServerRequestInterface $request): void
    {
        foreach ($request->getCookieParams() as $name => $value) {
            $cookie = new Cookie((string) $name, (string) $value);

            try {
                if (in_array($cookie->getName(), $this->encryption, true)) {
                    $cookie = $this->encryptor->decrypt($cookie);
                }

                if (in_array($cookie->getName(), $this->signature, true)) {
                    $cookie = $this->signer->validate($cookie);
                }

                $this->loaded->add($cookie);
            } catch (RuntimeException $e) {
                $this->tampered[$cookie->getName()] = $e->getMessage();
            }
        }
    }

    public function tampered(): array
    {
        return $this->tampered;
    }
}
