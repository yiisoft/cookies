<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cookies\RequestCookies\Exception\RequestCookieCollectionNotSetException;
use Yiisoft\Cookies\RequestCookies\RequestCookies;
use Yiisoft\Cookies\RequestCookies\RequestCookiesProvider;

final class RequestCookiesProviderTest extends TestCase
{
    private RequestCookiesProvider $cookieProvider;

    protected function setUp(): void
    {
        $this->cookieProvider = new RequestCookiesProvider();
    }

    public function testLocalCookieCollectionNotSetException(): void
    {
        $this->expectException(RequestCookieCollectionNotSetException::class);
        $this->cookieProvider->get();
    }

    public function testSetAndGet(): void
    {
        $collection = new RequestCookies(['test' => 'value']);
        $this->cookieProvider->set($collection);

        $requestCookies = $this->cookieProvider->get();
        $this->assertTrue($requestCookies->has('test'));
    }
}
