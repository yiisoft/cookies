<?php

declare(strict_types=1);

namespace Yiisoft\Cookies;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

use function is_string;

final class CookieLoader implements MiddlewareInterface
{
    private CookieEncryptor $encryptor;
    private CookieSigner $signer;

    public function __construct(CookieEncryptor $encryptor, CookieSigner $signer)
    {
        $this->encryptor = $encryptor;
        $this->signer = $signer;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $encrypted = new CookieCollection();
        $signed = new CookieCollection();

        foreach ($request->getCookieParams() as $name => $value) {
            if (!is_string($name) || !is_string($value)) {
                continue;
            }

            $cookie = new Cookie($name, $value);

            try {
                if ($this->encryptor->isEncrypted($cookie)) {
                    $encrypted->add($this->encryptor->decrypt($cookie));
                }

                if ($this->signer->isSigned($cookie)) {
                    $signed->add($this->signer->validate($cookie));
                }
            } catch (RuntimeException $e) {
                // Do need to log something in this case?
            }
        }

        if (!$encrypted->isEmpty()) {
            $request = $request->withAttribute(CookieEncryptor::class, $encrypted);
        }

        if (!$signed->isEmpty()) {
            $request = $request->withAttribute(CookieSigner::class, $signed);
        }

        return $handler->handle($request);
    }
}
