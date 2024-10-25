<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cookies\RequestCookies\RequestCookiesProvider;
use Yiisoft\Cookies\RequestCookies\RequestCookiesProviderInterface;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

use function dirname;

final class ConfigTest extends TestCase
{
    public function testDi(): void
    {
        $container = $this->createContainer();

        $this->assertInstanceOf(
            RequestCookiesProvider::class,
            $container->get(RequestCookiesProviderInterface::class)
        );
    }

    private function createContainer(): Container
    {
        return new Container(
            ContainerConfig::create()->withDefinitions($this->getContainerDefinitions())
        );
    }

    private function getContainerDefinitions(): array
    {
        return require dirname(__DIR__) . '/config/di-web.php';
    }
}
