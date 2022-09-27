<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Cookies\Cookie;
use Yiisoft\Cookies\CookieSigner;
use Yiisoft\Security\Mac;

use function md5;

final class CookieSignerTest extends TestCase
{
    private string $key = 'test-key';
    private string $cookieName = 'test-name';

    public function signDataProvider(): array
    {
        return [
            'empty-value' => ['', $this->encode('')],
            'string-value' => ['value', $this->encode('value')],
            'number-value' => ['1234567890', $this->encode('1234567890')],
            'json-value' => ['{"bool":true,"int":123}', $this->encode('{"bool":true,"int":123}')],
        ];
    }

    /**
     * @dataProvider signDataProvider
     */
    public function testSign(string $value, string $expected): void
    {
        $cookie = new Cookie($this->cookieName, $value);
        $signer = new CookieSigner($this->key);
        $signed = $signer->sign($cookie);

        $this->assertNotSame($cookie, $signed);
        $this->assertNotSame($expected, $cookie->getValue());
        $this->assertSame($expected, $signed->getValue());
    }

    /**
     * @dataProvider signDataProvider
     */
    public function testValidate(string $expected, string $value): void
    {
        $cookie = new Cookie($this->cookieName, $value);
        $signer = new CookieSigner($this->key);
        $unsigned = $signer->validate($cookie);

        $this->assertNotSame($cookie, $unsigned);
        $this->assertSame($expected, $unsigned->getValue());
    }

    public function testSignThrowExceptionForCookieValueIsAlreadySigned(): void
    {
        $prefix = md5(CookieSigner::class . $this->cookieName);
        $value = $prefix . (new Mac())->sign($prefix . 'value', $this->key);
        $cookie = new Cookie($this->cookieName, $value);
        $signer = new CookieSigner($this->key);

        $this->expectException(RuntimeException::class);
        $signer->sign($cookie);
    }

    public function invalidValidateDataProvider(): array
    {
        return [
            'empty-value' => ['', 'The "' . $this->cookieName . '" cookie value is not validly signed.'],
            'not-signed-value' => ['value', 'The "' . $this->cookieName . '" cookie value is not validly signed.'],
            'tampered-value' => [$this->encode('value') . '.', 'The "' . $this->cookieName . '" cookie value was tampered with.'],
            'signature-without-prefix' => [$this->encode('value', false), 'The "' . $this->cookieName . '" cookie value is not validly signed.'],
        ];
    }

    /**
     * @dataProvider invalidValidateDataProvider
     */
    public function testValidateThrowExceptionForInvalidSignedValue(string $value, string $message): void
    {
        $cookie = new Cookie($this->cookieName, $value);
        $signer = new CookieSigner($this->key);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);
        $signer->validate($cookie);
    }

    public function isSignedDataProvider(): array
    {
        return [
            'empty-value' => ['', false],
            'empty-signed-value' => [$this->encode(''), true],
            'string-value' => ['value', false],
            'string-signed-value' => [$this->encode('value'), true],
            'number-value' => ['1234567890', false],
            'number-signed-value' => [$this->encode('1234567890'), true],
            'json-value' => ['{"bool":true,"int":123}', false],
            'json-signed-value' => [$this->encode('{"bool":true,"int":123}'), true],
            'signature-without-prefix' => [$this->encode('value', false), false],
        ];
    }

    /**
     * @dataProvider isSignedDataProvider
     */
    public function testIsSigned(string $value, bool $isSigned): void
    {
        $cookie = new Cookie($this->cookieName, $value);
        $signer = new CookieSigner($this->key);

        if ($isSigned) {
            $this->assertTrue($signer->isSigned($cookie));
        } else {
            $this->assertFalse($signer->isSigned($cookie));
        }
    }

    private function encode(string $value, bool $withFirstPrefix = true): string
    {
        $prefix = md5(CookieSigner::class . $this->cookieName);
        return ($withFirstPrefix ? $prefix : '') . (new Mac())->sign($prefix . $value, $this->key);
    }
}
