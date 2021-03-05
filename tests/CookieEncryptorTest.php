<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Cookies\Cookie;
use Yiisoft\Cookies\CookieEncryptor;
use Yiisoft\Security\Crypt;

use function md5;

final class CookieEncryptorTest extends TestCase
{
    private string $key = 'test-key';
    private string $cookieName = 'test-name';

    public function cryptDataProvider(): array
    {
        return [
            'empty-value' => [''],
            'string-value' => ['value'],
            'number-value' => ['1234567890'],
            'json-value' => ['{"bool":true,"int":123}'],
        ];
    }

    /**
     * @dataProvider cryptDataProvider
     *
     * @param string $value
     */
    public function testEncryptAndDecrypt(string $value): void
    {
        $cookie = new Cookie($this->cookieName, $value);
        $encryptor = new CookieEncryptor($this->key);

        $encrypted = $encryptor->encrypt($cookie);
        $this->assertNotSame($cookie, $encrypted);

        $prefix = md5(CookieEncryptor::class . $this->cookieName);
        $this->assertStringContainsString($prefix, $encrypted->getValue());

        $decrypted = $encryptor->decrypt($encrypted);
        $this->assertNotSame($encrypted, $decrypted);

        $this->assertNotSame($value, $encrypted->getValue());
        $this->assertSame($value, $decrypted->getValue());
    }

    public function testEncryptThrowExceptionForCookieValueIsAlreadyEncrypted(): void
    {
        $cookie = new Cookie($this->cookieName, $this->encode('value'));
        $encryptor = new CookieEncryptor($this->key);

        $this->expectException(RuntimeException::class);
        $encryptor->encrypt($cookie);
    }

    public function invalidDecryptDataProvider(): array
    {
        return [
            'empty-value' => [''],
            'not-encrypted-value' => ['value'],
            'tampered-value' => [$this->encode('value') . '.'],
            'signature-without-prefix' => [$this->encode('value', false)],
        ];
    }

    /**
     * @dataProvider invalidDecryptDataProvider
     *
     * @param string $value
     */
    public function testDecryptThrowExceptionForInvalidEncryptedValue(string $value): void
    {
        $cookie = new Cookie($this->cookieName, $value);
        $encryptor = new CookieEncryptor($this->key);

        $this->expectException(RuntimeException::class);
        $encryptor->decrypt($cookie);
    }

    public function isEncryptedDataProvider(): array
    {
        return [
            'empty-value' => ['', false],
            'empty-encrypted-value' => [$this->encode(''), true],
            'string-value' => ['value', false],
            'string-encrypted-value' => [$this->encode('value'), true],
            'number-value' => ['1234567890', false],
            'number-encrypted-value' => [$this->encode('1234567890'), true],
            'json-value' => ['{"bool":true,"int":123}', false],
            'json-encrypted-value' => [$this->encode('{"bool":true,"int":123}'), true],
            'signature-without-prefix' => [$this->encode('value', false), false],
        ];
    }

    /**
     * @dataProvider isEncryptedDataProvider
     *
     * @param string $value
     * @param bool $isEncrypted
     */
    public function testIsEncrypted(string $value, bool $isEncrypted): void
    {
        $cookie = new Cookie($this->cookieName, $value);
        $encryptor = new CookieEncryptor($this->key);

        if ($isEncrypted) {
            $this->assertTrue($encryptor->isEncrypted($cookie));
        } else {
            $this->assertFalse($encryptor->isEncrypted($cookie));
        }
    }

    private function encode(string $value, bool $withPrefix = true): string
    {
        $prefix = $withPrefix ? md5(CookieEncryptor::class . $this->cookieName) : '';
        return $prefix . rawurlencode((new Crypt())->encryptByKey($value, $this->key, $this->cookieName));
    }
}
