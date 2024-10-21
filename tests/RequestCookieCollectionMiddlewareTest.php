<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\Tests;

use PHPUnit\Framework\TestCase;
use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\StreamFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Cookies\RequestCookieCollectionMiddleware;
use Yiisoft\Cookies\RequestCookieProvider;

class RequestCookieCollectionMiddlewareTest extends TestCase
{
    private RequestCookieProvider $cookieProvider;

    protected function setUp(): void
    {
        $this->cookieProvider = new RequestCookieProvider();
    }

    public function testProcessHas(): void
    {
        $request = $this->createServerRequest([
            'name' => 'value',
            'lang' => 'Ru-ru',
            'theme' => 'dark'
        ]);
        $middleware = $this->createCookieMiddleware();
        $middleware->process($request, $this->createRequestHandler());
        $collection = $this->cookieProvider->get();

        $this->assertTrue($collection->has('name'));

        $this->assertTrue($collection->has('lang'));

        $this->assertTrue($collection->has('theme'));
    }

    public function testProcessGetCookies(): void
    {
        $request = $this->createServerRequest([
            'name' => 'value',
            'lang' => 'Ru-ru',
            'theme' => 'dark'
        ]);

        $middleware = $this->createCookieMiddleware();
        $middleware->process($request, $this->createRequestHandler());
        $collection = $this->cookieProvider->get();

        $this->assertSame('value', $collection->getValue('name'));

        $this->assertSame('Ru-ru', $collection->getValue('lang'));

        $this->assertSame('dark', $collection->getValue('theme'));
    }

    private function createCookieMiddleware(): RequestCookieCollectionMiddleware
    {
        return new RequestCookieCollectionMiddleware($this->cookieProvider);
    }

    private function createServerRequest(array $cookieParams = []): ServerRequestInterface
    {
        return (new ServerRequest())->withCookieParams($cookieParams);
    }

    private function createRequestHandler(array $cookies = []): RequestHandlerInterface
    {
        return new class ($cookies) implements RequestHandlerInterface {
            private array $cookies;

            public function __construct(array $cookies)
            {
                $this->cookies = $cookies;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $content = '';

                foreach ($request->getCookieParams() as $name => $value) {
                    $content .= "{$name}:{$value},";
                }

                $stream = (new StreamFactory())->createStream(rtrim($content, ','));
                $response = (new Response())->withBody($stream);

                foreach ($this->cookies as $cookie) {
                    $response = $cookie->addToResponse($response);
                }

                return $response;
            }
        };
    }
}
