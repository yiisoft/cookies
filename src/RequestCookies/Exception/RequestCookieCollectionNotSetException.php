<?php

declare(strict_types=1);

namespace Yiisoft\Cookies\RequestCookies\Exception;

use LogicException;
use Throwable;

/**
 * Thrown when Request cookie collect isn't set before.
 */
final class RequestCookieCollectionNotSetException extends LogicException
{
    public function __construct(int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('Request cookie collect is not set.', $code, $previous);
    }
}
