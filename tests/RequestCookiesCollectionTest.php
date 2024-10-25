<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cookies\RequestCookies\RequestCookies;

final class RequestCookiesCollectionTest extends TestCase
{

    public function testGet(): void
    {
        $collection = new RequestCookies(['key' => 'value']);
        $this->assertEquals('value', $collection->get('key'));
    }

    public function testGetNonExisting(): void
    {
        $collection = new RequestCookies([]);
        $this->assertEquals(null, $collection->get('test'));
    }

    public function testExpireWithNonExistingKey(): void
    {
        $collection = new RequestCookies(['key' => 'value', 'test' => 'testVal']);

        $this->assertTrue($collection->has('key'));
        $this->assertFalse($collection->has('test2'));
    }
}
