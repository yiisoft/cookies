<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cookies\Cookie;
use Yiisoft\Cookies\Exception\RequestCookieCollectionNotSetException;
use Yiisoft\Cookies\RequestCookieCollection;
use Yiisoft\Cookies\RequestCookieProvider;

final class RequestCookieProviderTest extends TestCase
{
    private RequestCookieProvider $cookieProvider;

    protected function setUp(): void
    {
        $this->cookieProvider = new RequestCookieProvider();
    }

    public function testLocalCookieCollectionNotSetException(): void
    {
        $this->expectException(RequestCookieCollectionNotSetException::class);
        $this->cookieProvider->get();
    }

    public function testSetAndGet(): void
    {
        $cookie = new Cookie('test');
        $collection = new RequestCookieCollection([$cookie]);
        $this->cookieProvider->set($collection);

        $collectionGet = $this->cookieProvider->get();
        $this->assertTrue($collectionGet->has('test'));
    }
}
