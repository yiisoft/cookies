<?php

declare(strict_types=1);

namespace Yiisoft\Cookies;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Stores cookies request  {@see RequestCookieProviderInterface}.
 * You need to add this into your application middleware stack.
 */
final class RequestCookieCollectionMiddleware implements MiddlewareInterface
{
    private RequestCookieProviderInterface $cookieProvider;

    public function __construct(RequestCookieProviderInterface $cookieProvider)
    {
        $this->cookieProvider = $cookieProvider;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookies = $this->collectCookies($request);
        $this->cookieProvider->set(new RequestCookieCollection($cookies));
        return $handler->handle($request);
    }

    private function collectCookies(ServerRequestInterface $request): array
    {
        $collection = [];
        foreach ($request->getCookieParams() as $name => $value) {
            if (!is_string($name) || !is_string($value)) {
                continue;
            }
            $collection[] = (new Cookie($name, $value));
        }
        return $collection;
    }
}
