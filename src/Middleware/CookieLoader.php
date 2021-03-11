<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Cookies\CookieManager;

final class CookieLoader implements MiddlewareInterface
{
    private CookieManager $manager;

    public function __construct(CookieManager $manager)
    {
        $this->manager = $manager;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->manager->loadFromRequest($request);
        return $handler->handle($request);
    }
}
