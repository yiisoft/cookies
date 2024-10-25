<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\Tests;

use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\StreamFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Cookies\RequestCookies\RequestCookiesCollectorMiddleware;
use Yiisoft\Cookies\RequestCookies\RequestCookiesProvider;

class RequestCookiesCollectionMiddlewareTest extends TestCase
{
    private RequestCookiesProvider $cookieProvider;

    protected function setUp(): void
    {
        $this->cookieProvider = new RequestCookiesProvider();
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

        $this->assertSame('value', $collection->get('name'));

        $this->assertSame('Ru-ru', $collection->get('lang'));

        $this->assertSame('dark', $collection->get('theme'));
    }

    private function createCookieMiddleware(): RequestCookiesCollectorMiddleware
    {
        return new RequestCookiesCollectorMiddleware($this->cookieProvider);
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
