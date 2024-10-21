<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Cookies\Cookie;
use Yiisoft\Cookies\RequestCookieCollection;

final class LocalCookieCollectionTest extends TestCase
{
    public function testConstructorWithInvalidArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new RequestCookieCollection([new Cookie('test'), 'string']);
    }

    public function testGet(): void
    {
        $cookie = new Cookie('test');
        $collection = new RequestCookieCollection([$cookie]);
        $this->assertEquals($cookie, $collection->get('test'));
    }

    public function testGetNonExisting(): void
    {
        $collection = new RequestCookieCollection([]);
        $this->assertEquals(null, $collection->get('test'));
    }

    public function testGetValue(): void
    {
        $cookie = new Cookie('test', 'testVal');
        $collection = new RequestCookieCollection([$cookie]);

        $this->assertEquals('testVal', $collection->getValue('test'));
    }

    public function testExpireWithNonExistingKey(): void
    {
        $cookie = new Cookie('test', 'testVal');
        $collection = new RequestCookieCollection([$cookie]);

        $this->assertTrue($collection->has('test'));
        $this->assertFalse($collection->has('test2'));
    }
}
