<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Cookies\Cookie;
use Yiisoft\Cookies\CookieSigner;
use Yiisoft\Security\Mac;

use function array_merge;
use function hash_hmac_algos;

final class CookieSignerTest extends TestCase
{
    private string $key = 'test-key';

    public function signDataProvider(): array
    {
        $mac = new Mac();

        return [
            'empty-value' => ['', $mac->sign('__', $this->key)],
            'string-value' => ['value', $mac->sign('__value', $this->key)],
            'number-value' => ['1234567890', $mac->sign('__1234567890', $this->key)],
            'json-value' => ['{"bool":true,"int":123}', $mac->sign('__{"bool":true,"int":123}', $this->key)],
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
        $this->assertSame($expected, $unsigned->getValue());
    }

    public function invalidValidateDataProvider(): array
    {
        $mac = new Mac();

        return [
            'not-signed-value' => ['value'],
            'tampered-value' => [$mac->sign('__value', $this->key) . '.'],
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

    public function isSignedDataProvider(): array
    {
        $result = [];
        $items = static function (string $algorithm, string $key): array {
            $mac = new Mac($algorithm);

            return [
                "{$algorithm}-empty-value" => [$algorithm, '', false],
                "{$algorithm}-empty-signed-value" => [$algorithm, $mac->sign('__', $key), true],
                "{$algorithm}-string-value" => [$algorithm, 'value', false],
                "{$algorithm}-string-signed-value" => [$algorithm, $mac->sign('__value', $key), true],
                "{$algorithm}-number-value" => [$algorithm, '1234567890', false],
                "{$algorithm}-number-signed-value" => [$algorithm, $mac->sign('__1234567890', $key), true],
                "{$algorithm}-json-value" => [$algorithm, '{"bool":true,"int":123}', false],
                "{$algorithm}-json-signed-value" => [$algorithm, $mac->sign('__{"bool":true,"int":123}', $key), true],
                "{$algorithm}-signature-without-separator" => [
                    $algorithm,
                    'b95d4abec7c27ec87fb54da1621f9942948879e4-value',
                    false,
                ],
            ];
        };

        foreach (hash_hmac_algos() as $algorithm) {
            $result = array_merge($result, $items($algorithm, $this->key));
        }

        return $result;
    }

    /**
     * @dataProvider isSignedDataProvider
     *
     * @param string $algorithm
     * @param string $value
     * @param bool $isSigned
     */
    public function testIsSigned(string $algorithm, string $value, bool $isSigned): void
    {
        $cookie = new Cookie('test', $value);
        $signer = new CookieSigner($this->key, new Mac($algorithm));

        if ($isSigned) {
            $this->assertTrue($signer->isSigned($cookie));
        } else {
            $this->assertFalse($signer->isSigned($cookie));
        }
    }
}
