<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\RequestCookies;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Stores cookies request  {@see RequestCookiesProviderInterface}.
 * You need to add this into your application middleware stack.
 */
final class RequestCookiesCollectorMiddleware implements MiddlewareInterface
{
    private RequestCookiesProviderInterface $cookieProvider;

    public function __construct(RequestCookiesProviderInterface $cookieProvider)
    {
        $this->cookieProvider = $cookieProvider;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookies = $this->collectCookies($request);
        $this->cookieProvider->set(new RequestCookies($cookies));
        return $handler->handle($request);
    }

    /**
     * @param ServerRequestInterface $request
     * @return array<string, string>
     */
    private function collectCookies(ServerRequestInterface $request): array
    {
        $collection = [];
        foreach ($request->getCookieParams() as $name => $value) {
            if (!is_string($name) || !is_string($value)) {
                continue;
            }
            $collection[$name] = $value;
        }
        return $collection;
    }
}
