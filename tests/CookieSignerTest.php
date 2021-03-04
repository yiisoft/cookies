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
    public function testUnsign(string $expected, string $value): void
    {
        $cookie = new Cookie('test', $value);
        $signer = new CookieSigner($this->key);
        $unsigned = $signer->unsign($cookie);

        $this->assertNotSame($cookie, $unsigned);
        $this->assertNotSame($expected, $cookie->getValue());
        $this->assertSame($expected, $unsigned->getValue());
    }

    public function invalidUnsignDataProvider(): array
    {
        $mac = new Mac();

        return [
            'not-signed-value' => ['value'],
            'tampered-value' => [$mac->sign('value', $this->key) . '.'],
        ];
    }

    /**
     * @dataProvider invalidUnsignDataProvider
     *
     * @param string $value
     */
    public function testUnsingWithNotSignedValue(string $value): void
    {
        $cookie = new Cookie('test', $value);
        $signer = new CookieSigner($this->key);

        $this->expectException(RuntimeException::class);
        $signer->unsign($cookie);
    }

    public function validateDataProvider(): array
    {
        $mac = new Mac();

        return [
            'empty-value' => [$mac->sign('', $this->key)],
            'string-value' => [$mac->sign('value', $this->key)],
            'number-value' => [$mac->sign('1234567890', $this->key)],
            'json-value' => [$mac->sign('{"bool":true,"int":123}', $this->key)],
        ];
    }

    /**
     * @dataProvider validateDataProvider
     *
     * @param string $value
     */
    public function testValidate(string $value): void
    {
        $cookie = new Cookie('test', $value);
        $signer = new CookieSigner($this->key, new Mac());

        $this->assertTrue($signer->validate($cookie));
    }

    public function testSingAndValidateWithTamperedValue(): void
    {
        $mac = new Mac();
        $cookie = new Cookie('test', 'value');
        $signer = new CookieSigner($this->key, $mac);
        $signed = $signer->sign($cookie);

        $this->assertFalse($signer->validate($cookie));
        $this->assertTrue($signer->validate($signed));

        $tampered = $signed->withValue($signed->getValue() . '.');
        $this->assertFalse($signer->validate($tampered));
    }
}
