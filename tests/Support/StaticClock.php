<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\Tests\Support;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final class StaticClock implements ClockInterface
{
    private DateTimeImmutable $now;

    public function __construct(DateTimeImmutable $now)
    {
        $this->now = $now;
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
