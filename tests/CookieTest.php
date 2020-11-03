<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\Tests;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Yiisoft\Cookies\Cookie;

final class CookieTest extends TestCase
{

    private function getCookieHeader(Cookie $cookie): string
    {
        $response = new Response();
        $response = $cookie->addToResponse($response);
        return $response->getHeaderLine('Set-Cookie');
    }

    public function testInvalidName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Cookie('test[]', '42');
    }

    public function testDefaults(): void
    {
        $cookie = new Cookie('test', '42');
        $this->assertSame('test=42; Path=/; Secure; HttpOnly; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testWithValue(): void
    {
        $cookie = (new Cookie('test'))->withValue('42');
        $this->assertSame('test=42; Path=/; Secure; HttpOnly; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testValueThatIsEncoded(): void
    {
        $cookieString = (string)(new Cookie('test'))->withValue(';');
        $this->assertSame('test=%3B; Path=/; Secure; HttpOnly; SameSite=Lax', $cookieString);
    }

    public function testWithExpires(): void
    {
        $expireDateTime = new \DateTime('+1 year');
        $expireDateTime->setTimezone(new \DateTimeZone('GMT'));
        $formattedDateTime = $expireDateTime->format(\DateTimeInterface::RFC7231);
        $maxAge = $expireDateTime->getTimestamp() - time();

        $cookie = (new Cookie('test', '42'))->withExpires($expireDateTime);

        $this->assertSame("test=42; Expires=$formattedDateTime; Max-Age=$maxAge; Path=/; Secure; HttpOnly; SameSite=Lax", $this->getCookieHeader($cookie));
    }

    public function testIsExpiredTrue(): void
    {
        $cookie = (new Cookie('test', '42'))->withExpires((new \DateTimeImmutable('-5 years')));
        $this->assertTrue($cookie->isExpired());
    }

    public function testIsExpiredFalse(): void
    {
        $cookie = (new Cookie('test', '42'))->withExpires((new \DateTimeImmutable('+5 years')));
        $this->assertFalse($cookie->isExpired());
    }

    public function testWithMaxAge(): void
    {
        $formattedExpire = (new \DateTimeImmutable())->setTimestamp(time() + 3600)->format(\DateTimeInterface::RFC7231);
        $cookie = (new Cookie('test', '42'))->withMaxAge(new \DateInterval('PT3600S'));

        $this->assertSame("test=42; Expires=$formattedExpire; Max-Age=3600; Path=/; Secure; HttpOnly; SameSite=Lax", $this->getCookieHeader($cookie));
    }

    public function testExpire(): void
    {
        $cookie = (new Cookie('test', '42'))->expire();
        $this->assertTrue($cookie->isExpired());
    }

    public function testNegativeInterval(): void
    {
        $formattedExpire = (new \DateTimeImmutable())->setTimestamp(time() - 3600)->format(\DateTimeInterface::RFC7231);
        $negativeInterval = new \DateInterval('PT3600S');
        $negativeInterval->invert = 1;
        $cookie = (new Cookie('test', '42'))->withMaxAge($negativeInterval);

        $this->assertSame("test=42; Expires=$formattedExpire; Max-Age=-3600; Path=/; Secure; HttpOnly; SameSite=Lax", $this->getCookieHeader($cookie));
    }

    public function testWithDomain(): void
    {
        $cookie = (new Cookie('test', '42'))->withDomain('yiiframework.com');
        $this->assertSame('test=42; Domain=yiiframework.com; Path=/; Secure; HttpOnly; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testExpireWhenBrowserIsClosed(): void
    {
        $cookie = (new Cookie('test', '42'))->expireWhenBrowserIsClosed();
        $this->assertSame('test=42; Path=/; Secure; HttpOnly; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testWithPath(): void
    {
        $cookie = (new Cookie('test', '42'))->withPath('/test');
        $this->assertSame('test=42; Path=/test; Secure; HttpOnly; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testInvalidPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Cookie('test', '42'))->withPath(';invalid');
    }

    public function testWithSecure(): void
    {
        $defaultSecure = (new Cookie('test', '42'))->withSecure();
        $cookie = (new Cookie('test', '42'))->withSecure(false);

        $this->assertSame('test=42; Path=/; Secure; HttpOnly; SameSite=Lax', $this->getCookieHeader($defaultSecure));
        $this->assertSame('test=42; Path=/; HttpOnly; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testHttpOnly(): void
    {
        $defaultHttpOnly = (new Cookie('test', '42'))->withHttpOnly();
        $cookie = (new Cookie('test', '42'))->withHttpOnly(false);

        $this->assertSame('test=42; Path=/; Secure; HttpOnly; SameSite=Lax', $this->getCookieHeader($defaultHttpOnly));
        $this->assertSame('test=42; Path=/; Secure; SameSite=Lax', $this->getCookieHeader($cookie));
    }

    public function testInvalidSameSite(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Cookie('test', '42'))->withSameSite('invalid');
    }

    public function testSameSiteNone(): void
    {
        $cookie = (new Cookie('test', '42'))->withSameSite(Cookie::SAME_SITE_NONE);
        $this->assertSame('test=42; Path=/; Secure; HttpOnly; SameSite=None', $this->getCookieHeader($cookie));
    }

    public function fromCookieStringDataProvider(): array
    {
        $maxAgeDate = new \DateTimeImmutable('+60 minutes');
        $expireDate = new \DateTimeImmutable('2012/12/7 10:00 UTC+0');

        return [
            [
                'sessionId=e8bb43229de9; Domain=foo.example.com; '
                . 'Expires=' . $maxAgeDate->format(\DateTimeInterface::RFC7231) . '; '
                . 'Max-Age=3600; WeirdKey; Path=/test; Secure; HttpOnly; SameSite=Strict; ExtraKey',
                new Cookie(
                    'sessionId',
                    'e8bb43229de9',
                    $maxAgeDate,
                    'foo.example.com',
                    '/test',
                    true,
                    true,
                    Cookie::SAME_SITE_STRICT
                )
            ],
            [
                'sessionId=e8bb43229de9; Domain=foo.example.com=test; '
                . 'Expires=' . $expireDate->format(\DateTimeInterface::RFC7231) . '; ',
                new Cookie(
                    'sessionId',
                    'e8bb43229de9',
                    $expireDate,
                    'foo.example.com=test',
                    null,
                    false,
                    false,
                    null
                )
            ],
            [
                'sessionId=e8bb43229de9; Domain=foo.example.com=test; Max-Age=bla',
                new Cookie(
                    'sessionId',
                    'e8bb43229de9',
                    new \DateTimeImmutable(),
                    'foo.example.com=test',
                    null,
                    false,
                    false,
                    null
                )
            ],
        ];
    }

    /**
     * @dataProvider fromCookieStringDataProvider
     */
    public function testFromCookieString(string $setCookieString, Cookie $cookie): void
    {
        $cookie2 = Cookie::fromCookieString($setCookieString);
        $this->assertSame((string)$cookie, (string)$cookie2);
    }

    public function testFromCookieStringWithInvalidString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Cookie::fromCookieString('');
    }

    public function testGetters(): void
    {
        $cookie = new Cookie('test', '', null, null, null, null, null, null);
        $this->assertEquals('test', $cookie->getName());
        $this->assertEquals('', $cookie->getValue());
        $this->assertNull($cookie->getExpires());
        $this->assertNull($cookie->getDomain());
        $this->assertNull($cookie->getPath());
        $this->assertFalse($cookie->isSecure());
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertNull($cookie->getSameSite());

        $cookie = $cookie->withValue('testValue');
        $this->assertEquals('testValue', $cookie->getValue());

        $expiry = new \DateTimeImmutable();
        $cookie = $cookie->withExpires($expiry);
        $this->assertEquals($expiry->getTimestamp(), $cookie->getExpires()->getTimestamp());

        $cookie = $cookie->withDomain('yiiframework.com');
        $this->assertEquals('yiiframework.com', $cookie->getDomain());

        $cookie = $cookie->withPath('/path');
        $this->assertEquals('/path', $cookie->getPath());

        $cookie = $cookie->withSecure(true);
        $this->assertTrue($cookie->isSecure());

        $cookie = $cookie->withHttpOnly(true);
        $this->assertTrue($cookie->isHttpOnly());

        $cookie = $cookie->withSameSite(Cookie::SAME_SITE_LAX);
        $this->assertEquals(Cookie::SAME_SITE_LAX, $cookie->getSameSite());
    }

    public function testImmutability(): void
    {
        $expires = new \DateTime();
        $original = new Cookie('test', 'value', $expires);
        $withExpires = $original->withExpires($expires);
        $expires->setDate(2000, 12, 7);

        $this->assertNotEquals(2000, $original->getExpires()->format('Y'));
        $this->assertNotSame($original, $withExpires);
        $this->assertNotEquals(2000, $withExpires->getExpires()->format('Y'));

        $this->assertNotSame($original, $original->withDomain('test'));
        $this->assertNotSame($original, $original->withHttpOnly(true));
        $this->assertNotSame($original, $original->withMaxAge(new \DateInterval('P1D')));
        $this->assertNotSame($original, $original->withPath('test'));
        $this->assertNotSame($original, $original->withSameSite(Cookie::SAME_SITE_LAX));
        $this->assertNotSame($original, $original->withSecure(true));
        $this->assertNotSame($original, $original->withValue('value'));
        $this->assertNotSame($original, $original->expire());
        $this->assertNotSame($original, $original->expireWhenBrowserIsClosed());
    }
}
