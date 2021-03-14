<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Cookies\CookieStorage;

final class CookieLoader implements MiddlewareInterface
{
    private CookieStorage $storage;

    public function __construct(CookieStorage $storage)
    {
        $this->storage = $storage;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $this->storage->addToRequest($request);
        return $handler->handle($request);
    }
}
