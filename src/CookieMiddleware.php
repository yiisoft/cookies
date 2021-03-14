<?php

declare(strict_types=1);

namespace Yiisoft\Cookies;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Yiisoft\Strings\WildcardPattern;

use function explode;
use function is_string;

/**
 * Validates and decrypts the values of the request cookie parameters,
 * encrypts and signs the values of the response `Set-Cookie` headers.
 */
final class CookieMiddleware implements MiddlewareInterface
{
    private CookieEncryptor $encryptor;
    private CookieSigner $signer;
    private LoggerInterface $logger;

    /**
     * @var string[] The names of the cookies that need to be encrypted/decrypted.
     */
    private array $encryption = [];

    /**
     * @var string[] The names of the cookies that need to be signed/validated.
     */
    private array $signature = [];

    /**
     * @param LoggerInterface $logger The logger instance.
     * @param CookieEncryptor $encryptor The encryptor instance.
     * @param CookieSigner $signer The signer instance.
     * @param string[] $namePatterns The array keys are cookie name patterns {@see \Yiisoft\Strings\WildcardPattern},
     * and values are class names of {@see \Yiisoft\Cookies\CookieEncryptor} or {@see \Yiisoft\Cookies\CookieSigner}.
     */
    public function __construct(
        LoggerInterface $logger,
        CookieEncryptor $encryptor,
        CookieSigner $signer,
        array $namePatterns = []
    ) {
        $this->logger = $logger;
        $this->encryptor = $encryptor;
        $this->signer = $signer;

        foreach ($namePatterns as $pattern => $class) {
            if ($class === CookieEncryptor::class) {
                $this->encryption[] = (string) $pattern;
                continue;
            }

            if ($class === CookieSigner::class) {
                $this->signature[] = (string) $pattern;
            }
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $this->decodeRequestCookieParams($request);
        $response = $handler->handle($request);
        return $this->encodeResponseSetCookieHeaders($response);
    }

    /**
     * Decrypts and validates the request cookie parameters.
     *
     * If cookie name patterns have been set and there are matches, the values will
     * be verified and validated/decrypted. Otherwise, the values will not be changed.
     *
     * If the value of the cookie parameter is tampered with then this
     * parameter will be excluded information about it will be logged.
     *
     * @param ServerRequestInterface $request
     *
     * @return ServerRequestInterface
     */
    private function decodeRequestCookieParams(ServerRequestInterface $request): ServerRequestInterface
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
                $this->logger->info($e->getMessage(), ['exception' => $e]);
                continue;
            }

            $cookieParams[$name] = $value;
        }

        return $request->withCookieParams($cookieParams);
    }

    /**
     * Encrypts and signs the values of `Set-Cookie` header and add to the response.
     *
     * If cookie name patterns have been set and there are matches, the values will
     * be encrypted/signed. Otherwise, the values will not be changed.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    private function encodeResponseSetCookieHeaders(ResponseInterface $response): ResponseInterface
    {
        $changed = false;
        $headers = [];

        foreach ($response->getHeader('Set-Cookie') as $key => $header) {
            [$name] = explode('=', $header, 2);

            if ($this->isSecurity($name, $this->encryption)) {
                $cookie = Cookie::fromCookieString($header);
                $cookie = $this->encryptor->isEncrypted($cookie) ? $cookie : $this->encryptor->encrypt($cookie);
                $headers[$key] = (string) $cookie;
                $changed = true;
                continue;
            }

            if ($this->isSecurity($name, $this->signature)) {
                $cookie = Cookie::fromCookieString($header);
                $cookie = $this->signer->isSigned($cookie) ? $cookie : $this->signer->sign($cookie);
                $headers[$key] = (string) $cookie;
                $changed = true;
                continue;
            }

            $headers[$key] = $header;
        }

        if ($changed === false) {
            return $response;
        }

        $response = $response->withoutHeader('Set-Cookie');

        foreach ($headers as $header) {
            $response = $response->withAddedHeader('Set-Cookie', $header);
        }

        return $response;
    }

    /**
     * Checks whether the cookie name matches the set cookie name patterns.
     *
     * @param string $name The cookie name to check.
     * @param string[] $patterns The array keys are cookie name patterns {@see \Yiisoft\Strings\WildcardPattern}.
     *
     * @return bool Whether the cookie name matches the set cookie name patterns.
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
