<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\Tests;

use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\StreamFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Cookies\Cookie;
use Yiisoft\Cookies\CookieEncryptor;
use Yiisoft\Cookies\CookieMiddleware;
use Yiisoft\Cookies\CookieSigner;
use Yiisoft\Test\Support\Log\SimpleLogger;

use function rtrim;

final class CookieMiddlewareTest extends TestCase
{
    private const SECRET_KEY = '0my1xVkjCJnD_q1yr6lUxcAdpDlTMwiU';

    private CookieEncryptor $encryptor;
    private CookieSigner $signer;
    private SimpleLogger $logger;

    protected function setUp(): void
    {
        $this->encryptor = new CookieEncryptor(self::SECRET_KEY);
        $this->signer = new CookieSigner(self::SECRET_KEY);
        $this->logger = new SimpleLogger();
    }

    public function testProcess(): void
    {
        $encryption = new Cookie('encrypted', 'value');
        $encrypted = $this->encryptor->encrypt($encryption);
        $signature = new Cookie('signed', 'value');
        $signed = $this->signer->sign($signature);
        $request = $this->createServerRequest([
            'name' => 'value',
            $encrypted->getName() => $encrypted->getValue(),
            $signed->getName() => $signed->getValue(),
        ]);
        $middleware = $this->createCookieMiddleware([
            'encrypted' => CookieMiddleware::ENCRYPT,
            'signed' => CookieMiddleware::SIGN,
        ]);
        $response = $middleware->process($request, $this->createRequestHandler([$encryption, $signature]));
        $content = $response->getBody()->getContents();

        $this->assertTrue($this->encryptor->isEncrypted(Cookie::fromCookieString($response->getHeader('set-cookie')[0])));
        $this->assertTrue($this->signer->isSigned(Cookie::fromCookieString($response->getHeader('set-cookie')[1])));
        $this->assertSame((string) $signed, $response->getHeader('set-cookie')[1]);
        $this->assertSame('name:value,encrypted:value,signed:value', $content);
        $this->assertEmpty($this->logger->getMessages());
    }

    public function testProcessWithAlreadyEncodingResponseHeaderValues(): void
    {
        $encrypted = $this->encryptor->encrypt(new Cookie('encrypted', 'value'));
        $signed = $this->signer->sign(new Cookie('signed', 'value'));
        $request = $this->createServerRequest([
            'name' => 'value',
            $encrypted->getName() => $encrypted->getValue(),
            $signed->getName() => $signed->getValue(),
        ]);
        $middleware = $this->createCookieMiddleware([
            'encrypted' => CookieMiddleware::ENCRYPT,
            'signed' => CookieMiddleware::SIGN,
        ]);
        $response = $middleware->process($request, $this->createRequestHandler([$encrypted, $signed]));
        $content = $response->getBody()->getContents();

        $this->assertSame([(string) $encrypted, (string) $signed], $response->getHeader('set-cookie'));
        $this->assertSame('name:value,encrypted:value,signed:value', $content);
        $this->assertEmpty($this->logger->getMessages());
    }

    public function testProcessWithNamePatternsIsEmpty(): void
    {
        $cookie = new Cookie('name', 'value');
        $request = $this->createServerRequest([$cookie->getName() => $cookie->getValue()]);
        $middleware = $this->createCookieMiddleware();
        $response = $middleware->process($request, $this->createRequestHandler([$cookie]));
        $content = $response->getBody()->getContents();

        $this->assertSame([(string) $cookie], $response->getHeader('set-cookie'));
        $this->assertSame('name:value', $content);
        $this->assertEmpty($this->logger->getMessages());
    }

    public function testProcessWithNamePatternsAreMissingInRequest(): void
    {
        $cookie = new Cookie('name', 'value');
        $request = $this->createServerRequest([$cookie->getName() => $cookie->getValue()]);
        $middleware = $this->createCookieMiddleware(['encrypted' => CookieMiddleware::ENCRYPT]);
        $response = $middleware->process($request, $this->createRequestHandler([$cookie]));
        $content = $response->getBody()->getContents();

        $this->assertSame([(string) $cookie], $response->getHeader('set-cookie'));
        $this->assertSame('name:value', $content);
        $this->assertEmpty($this->logger->getMessages());
    }

    public function testProcessWithCookieParamsAndNamePatternsIsEmpty(): void
    {
        $middleware = $this->createCookieMiddleware();
        $response = $middleware->process($this->createServerRequest(), $this->createRequestHandler());
        $content = $response->getBody()->getContents();

        $this->assertSame([], $response->getHeader('set-cookie'));
        $this->assertSame('', $content);
        $this->assertEmpty($this->logger->getMessages());
    }

    public function testProcessWithCookieValueIsTamperedWith(): void
    {
        $cookie = $this->encryptor->encrypt(new Cookie('name', 'value'));
        $request = $this->createServerRequest([$cookie->getName() => "{$cookie->getValue()}."]);
        $middleware = $this->createCookieMiddleware(['name' => CookieMiddleware::ENCRYPT]);
        $response = $middleware->process($request, $this->createRequestHandler());
        $content = $response->getBody()->getContents();

        $this->assertSame([], $response->getHeader('set-cookie'));
        $this->assertSame('', $content);
        $this->assertSame(
            'The "name" cookie value was tampered with.',
            $this->logger->getMessages()[0]['message'],
        );
    }

    private function createCookieMiddleware(array $patterns = []): CookieMiddleware
    {
        return new CookieMiddleware($this->logger, $this->encryptor, $this->signer, $patterns);
    }

    private function createServerRequest(array $cookieParams = []): ServerRequestInterface
    {
        return (new ServerRequest())->withCookieParams($cookieParams);
    }

    private function createRequestHandler(array $cookies = []): RequestHandlerInterface
    {
        return new class($cookies) implements RequestHandlerInterface {
            private array $cookies;

            public function __construct(array $cookies)
            {
                $this->cookies = $cookies;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $content = '';

                foreach ($request->getCookieParams() as $name => $value) {
                    $content .= "{$name}:{$value},";
                }

                $stream = (new StreamFactory())->createStream(rtrim($content, ','));
                $response = (new Response())->withBody($stream);

                foreach ($this->cookies as $cookie) {
                    $response = $cookie->addToResponse($response);
                }

                return $response;
            }
        };
    }
}
