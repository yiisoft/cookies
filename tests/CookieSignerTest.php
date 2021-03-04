<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Cookies\Cookie;
use Yiisoft\Cookies\CookieSigner;
use Yiisoft\Security\Mac;

final class CookieSignerTest extends TestCase
{
    private string $key = 'test-key';

    public function signDataProvider(): array
    {
        $mac = new Mac();

        return [
            'empty-value' => ['', $mac->sign('', $this->key)],
            'string-value' => ['value', $mac->sign('value', $this->key)],
            'number-value' => ['1234567890', $mac->sign('1234567890', $this->key)],
            'json-value' => ['{"bool":true,"int":123}', $mac->sign('{"bool":true,"int":123}', $this->key)],
        ];
    }

    /**
     * @dataProvider signDataProvider
     *
     * @param string $value
     * @param string $expected
     */
    public function testSign(string $value, string $expected): void
    {
        $cookie = new Cookie('test', $value);
        $signer = new CookieSigner($this->key);
        $signed = $signer->sign($cookie);

        $this->assertNotSame($cookie, $signed);
        $this->assertNotSame($expected, $cookie->getValue());
        $this->assertSame($expected, $signed->getValue());
    }

    /**
     * @dataProvider signDataProvider
     *
     * @param string $expected
     * @param string $value
     */
    public function testValidate(string $expected, string $value): void
    {
        $cookie = new Cookie('test', $value);
        $signer = new CookieSigner($this->key);
        $unsigned = $signer->validate($cookie);

        $this->assertNotSame($cookie, $unsigned);
        $this->assertNotSame($expected, $cookie->getValue());
        $this->assertSame($expected, $unsigned->getValue());
    }

    public function invalidValidateDataProvider(): array
    {
        $mac = new Mac();

        return [
            'not-signed-value' => ['value'],
            'tampered-value' => [$mac->sign('value', $this->key) . '.'],
        ];
    }

    /**
     * @dataProvider invalidValidateDataProvider
     *
     * @param string $value
     */
    public function testValidateThrowExceptionForInvalidSignedValue(string $value): void
    {
        $cookie = new Cookie('test', $value);
        $signer = new CookieSigner($this->key);

        $this->expectException(RuntimeException::class);
        $signer->validate($cookie);
    }
}
