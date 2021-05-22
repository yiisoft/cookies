<?php

declare(strict_types=1);

namespace Yiisoft\Cookies;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Header;

use function in_array;
use function is_string;

/**
 * Represents a cookie and also helps adding Set-Cookie header to response in order to set a cookie.
 */
final class Cookie
{
    /**
     * Regular Expression used to validate cookie name.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-4.1.1
     * @link https://tools.ietf.org/html/rfc2616#section-2.2
     */
    private const PATTERN_TOKEN = '/^[a-zA-Z0-9!#$%&\' * +\- .^_`|~]+$/';

    /**
     * SameSite policy `Lax` will prevent the cookie from being sent by the browser in all cross-site browsing contexts
     * during CSRF-prone request methods (e.g. POST, PUT, PATCH etc).
     * E.g. a POST request from https://otherdomain.com to https://yourdomain.com will not include the cookie,
     * however a GET request will.
     * When a user follows a link from https://otherdomain.com to https://yourdomain.com it will include the cookie.
     * This is the default value in modern browsers.
     *
     * @see $sameSite
     */
    public const SAME_SITE_LAX = 'Lax';

    /**
     * SameSite policy `Strict` will prevent the cookie from being sent by the browser in all cross-site
     * browsing contexts regardless of the request method and even when following a regular link.
     * E.g. a GET request from https://otherdomain.com to https://yourdomain.com or a user following a link from
     * https://otherdomain.com to https://yourdomain.com will not include the cookie.
     *
     * @see $sameSite
     */
    public const SAME_SITE_STRICT = 'Strict';

    /**
     * SameSite policy `None` cookies will be sent in all contexts, i.e. sending cross-origin is allowed.
     * `None` requires the `Secure` attribute in latest browser versions.
     *
     * @see $sameSite
     */
    public const SAME_SITE_NONE = 'None';

    /**
     * @var string Name of the cookie.
     * A cookie name can be any US-ASCII characters, except control characters, spaces, or tabs.
     * It also must not contain a separator character like the following: ( ) < > @ , ; : \ " / [ ] ? = { }
     */
    private string $name;

    /**
     * @var string Value of the cookie.
     */
    private string $value;

    /**
     * @var bool Whether cookie value should be encoded.
     */
    private bool $encodeValue;

    /**
     * @var DateTimeInterface|null The maximum lifetime of the cookie.
     * If unspecified, the cookie becomes a session cookie, which will be removed
     * when the client shuts down.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-4.1.1
     * @link https://tools.ietf.org/html/rfc1123#page-55
     */
    private ?DateTimeInterface $expires = null;

    /**
     * @var string|null Host/domain to which the cookie will be sent.
     * If omitted, client will default to the host of the current URL, not including subdomains.
     * Multiple host/domain values are not allowed, but if a domain is specified,
     * then subdomains are always included.
     */
    private ?string $domain = null;

    /**
     * @var string|null The path on the server in which the cookie will be available on.
     * A cookie path can include any US-ASCII characters excluding control characters and semicolon.
     */
    private ?string $path = null;

    /**
     * @var bool|null Whether cookie should be sent via secure connection.
     * A secure cookie is only sent to the server when a request is made with the https: scheme.
     */
    private ?bool $secure = null;

    /**
     * @var bool|null Whether the cookie should be accessible only through the HTTP protocol.
     * By setting this property to true, the cookie will not be accessible by scripting languages,
     * such as JavaScript, which can effectively help to mitigate attacks against cross-site scripting (XSS).
     */
    private ?bool $httpOnly = null;

    /**
     * @var string|null Asserts that a cookie must not be sent with cross-origin requests.
     * This provides some protection against cross-site request forgery attacks (CSRF).
     *
     * @link https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html#samesite-cookie-attribute
     * More information about sameSite.
     */
    private ?string $sameSite = null;

    /**
     * Cookie constructor.
     *
     * @param string $name The name of the cookie.
     * @param string $value The value of of the cookie.
     * @param DateTimeInterface|null $expires The time the cookie expires.
     * @param string|null $domain The path on the server in which cookie will be available on.
     * @param string|null $path The host/domain that the cookie is available to.
     * @param bool|null $secure Whether the client should send back the cookie only over HTTPS connection.
     * @param bool|null $httpOnly Whether the cookie should be accessible only through the HTTP protocol.
     * @param string|null $sameSite Whether the cookie should be available for cross-site requests.
     * @param bool $encodeValue Whether cookie value should be encoded.
     *
     * @throws InvalidArgumentException When one or more arguments are not valid.
     */
    public function __construct(
        string $name,
        string $value = '',
        ?DateTimeInterface $expires = null,
        ?string $domain = null,
        ?string $path = '/',
        ?bool $secure = true,
        ?bool $httpOnly = true,
        ?string $sameSite = self::SAME_SITE_LAX,
        bool $encodeValue = true
    ) {
        if (!preg_match(self::PATTERN_TOKEN, $name)) {
            throw new InvalidArgumentException("The cookie name \"$name\" contains invalid characters or is empty.");
        }

        $this->name = $name;
        $this->value = $value;
        $this->encodeValue = $encodeValue;
        $this->expires = $expires !== null ? clone $expires : null;
        $this->domain = $domain;
        $this->setPath($path);
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
        $this->setSameSite($sameSite);
    }

    /**
     * Gets the name of the cookie.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Creates a cookie copy with a new value.
     *
     * @param $value string Value of the cookie.
     *
     * @return static
     *
     * @see $value for more information.
     */
    public function withValue(string $value): self
    {
        $new = clone $this;
        $new->value = $value;
        $new->encodeValue = true;
        return $new;
    }

    /**
     * Creates a cookie copy with a new value that will not be encoded.
     *
     * @param $value string Value of the cookie.
     */
    public function withRawValue(string $value): self
    {
        $new = clone $this;
        $new->value = $value;
        $new->encodeValue = false;
        return $new;
    }

    /**
     * Gets the value of the cookie.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Creates a cookie copy with a new time the cookie expires.
     *
     * @param DateTimeInterface $dateTime
     *
     * @return static
     *
     * @see $expires for more information.
     */
    public function withExpires(DateTimeInterface $dateTime): self
    {
        $new = clone $this;
        // Defensively clone the object to prevent further change
        $new->expires = clone $dateTime;
        return $new;
    }

    /**
     * Gets the expiry of the cookie.
     *
     * @return DateTimeImmutable|null
     */
    public function getExpires(): ?DateTimeImmutable
    {
        if ($this->expires === null) {
            return null;
        }

        // Can be replaced with DateTimeImmutable::createFromInterface in PHP 8.
        // Returns null on `setTimestamp()` failure.
        return (new DateTimeImmutable())->setTimestamp($this->expires->getTimestamp()) ?: null;
    }

    /**
     * Indicates whether the cookie is expired.
     * The cookie is expired when it has outdated `Expires`, or
     * zero or negative `Max-Age` attributes.
     *
     * @return bool Whether the cookie is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires !== null && $this->expires->getTimestamp() < time();
    }

    /**
     * Creates a cookie copy with a new lifetime set.
     * If zero or negative interval is passed, the cookie will expire immediately.
     *
     * @param DateInterval $interval Interval until the cookie expires.
     *
     * @return static
     */
    public function withMaxAge(DateInterval $interval): self
    {
        $new = clone $this;
        $new->expires = (new DateTimeImmutable())->add($interval);
        return $new;
    }

    /**
     * Returns modified cookie that will expire immediately.
     *
     * @return static
     */
    public function expire(): self
    {
        $new = clone $this;
        $new->expires = new DateTimeImmutable('-1 year');
        return $new;
    }

    /**
     * Will remove the expiration from the cookie which will convert the cookie
     * to session cookie, which will expire as soon as the browser is closed.
     *
     * @return static
     */
    public function expireWhenBrowserIsClosed(): self
    {
        $new = clone $this;
        $new->expires = null;
        return $new;
    }

    /**
     * Creates a cookie copy with a new domain set.
     *
     * @param string $domain
     *
     * @return static
     */
    public function withDomain(string $domain): self
    {
        $new = clone $this;
        $new->domain = $domain;
        return $new;
    }

    /**
     * Gets the domain of the cookie.
     *
     * @return string|null
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Creates a cookie copy with a new path set.
     *
     * @param string $path To be set for the cookie.
     *
     * @return static
     *
     * @see $path for more information.
     */
    public function withPath(string $path): self
    {
        $new = clone $this;
        $new->setPath($path);
        return $new;
    }

    private function setPath(?string $path): void
    {
        if ($path !== null && preg_match('/[\x00-\x1F\x7F\x3B]/', $path)) {
            throw new InvalidArgumentException("The cookie path \"$path\" contains invalid characters.");
        }

        $this->path = $path;
    }

    /**
     * Gets the path of the cookie.
     *
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Creates a cookie copy by making it secure or insecure.
     *
     * @param bool $secure Whether the cookie must be secure.
     *
     * @return static
     */
    public function withSecure(bool $secure = true): self
    {
        $new = clone $this;
        $new->secure = $secure;
        return $new;
    }

    /**
     * Whether the cookie is secure.
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->secure ?? false;
    }

    /**
     * Creates a cookie copy that would be accessible only through the HTTP protocol.
     *
     * @param bool $httpOnly
     *
     * @return static
     */
    public function withHttpOnly(bool $httpOnly = true): self
    {
        $new = clone $this;
        $new->httpOnly = $httpOnly;
        return $new;
    }

    /**
     * Whether the cookie can be accessed only through the HTTP protocol.
     *
     * @return bool
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly ?? false;
    }

    /**
     * Creates a cookie copy with SameSite attribute.
     *
     * @param string $sameSite
     *
     * @return static
     */
    public function withSameSite(string $sameSite): self
    {
        $new = clone $this;
        $new->setSameSite($sameSite);
        return $new;
    }

    private function setSameSite(?string $sameSite): void
    {
        if (
            $sameSite !== null
            && !in_array($sameSite, [self::SAME_SITE_LAX, self::SAME_SITE_STRICT, self::SAME_SITE_NONE], true)
        ) {
            throw new InvalidArgumentException('sameSite should be one of "Lax", "Strict" or "None".');
        }

        if ($sameSite === self::SAME_SITE_NONE) {
            // The "secure" flag is required for cookies that are marked as 'SameSite=None'
            // so that cross-site cookies can only be accessed over HTTPS
            // without it cookie will not be available for external access.
            $this->secure = true;
        }

        $this->sameSite = $sameSite;
    }

    /**
     * Gets the SameSite attribute.
     *
     * @return string|null
     */
    public function getSameSite(): ?string
    {
        return $this->sameSite;
    }

    /**
     * Adds the cookie to the response and returns it.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface Response with added cookie.
     */
    public function addToResponse(ResponseInterface $response): ResponseInterface
    {
        return $response->withAddedHeader(Header::SET_COOKIE, (string) $this);
    }

    /**
     * Returns the cookie as a header string.
     *
     * @return string The cookie header string.
     */
    public function __toString(): string
    {
        $cookieParts = [
            $this->name . '=' . ($this->encodeValue ? urlencode($this->value) : $this->value),
        ];

        if ($this->expires !== null) {
            $cookieParts[] = 'Expires=' . $this->expires->format(DateTimeInterface::RFC7231);
            $cookieParts[] = 'Max-Age=' . ($this->expires->getTimestamp() - time());
        }

        if ($this->domain !== null) {
            $cookieParts[] = 'Domain=' . $this->domain;
        }

        if ($this->path !== null) {
            $cookieParts[] = 'Path=' . $this->path;
        }

        if ($this->secure) {
            $cookieParts[] = 'Secure';
        }

        if ($this->httpOnly) {
            $cookieParts[] = 'HttpOnly';
        }

        if ($this->sameSite !== null) {
            $cookieParts[] = 'SameSite=' . $this->sameSite;
        }

        return implode('; ', $cookieParts);
    }

    /**
     * Parse `Set-Cookie` string and build Cookie object.
     *
     * @param string $string `Set-Cookie` header value to parse.
     *
     * @throws Exception
     *
     * @return static
     */
    public static function fromCookieString(string $string): self
    {
        /** @psalm-var list<string> $rawAttributes */
        $rawAttributes = preg_split('~\s*[;]\s*~', $string);

        // array_filter with empty callback is used to filter out all falsy values.
        $rawAttributes = array_filter($rawAttributes);

        $rawAttribute = array_shift($rawAttributes);

        if (!is_string($rawAttribute)) {
            throw new InvalidArgumentException('Cookie string must have at least name.');
        }

        [$cookieName, $cookieValue] = self::splitCookieAttribute($rawAttribute);

        /** @var array{name: string, value: string} $params */
        $params = [
            'name' => $cookieName,
            'value' => $cookieValue !== null ? urldecode($cookieValue) : '',
        ];

        while ($rawAttribute = array_shift($rawAttributes)) {
            /** @var string $attributeKey */
            [$attributeKey, $attributeValue] = self::splitCookieAttribute($rawAttribute);
            $attributeKey = strtolower($attributeKey);

            if ($attributeValue === null && !in_array($attributeKey, ['secure', 'httponly'], true)) {
                continue;
            }

            /** @var string $attributeValue */

            switch ($attributeKey) {
                case 'expires':
                    $params['expires'] = new DateTimeImmutable($attributeValue);
                    break;
                case 'max-age':
                    $params['expires'] = (new DateTimeImmutable())->setTimestamp(time() + (int)$attributeValue);
                    break;
                case 'domain':
                    $params['domain'] = $attributeValue;
                    break;
                case 'path':
                    $params['path'] = $attributeValue;
                    break;
                case 'secure':
                    $params['secure'] = true;
                    break;
                case 'httponly':
                    $params['httpOnly'] = true;
                    break;
                case 'samesite':
                    $params['sameSite'] = $attributeValue;
                    break;
            }
        }

        return new self(
            $params['name'],
            $params['value'],
            $params['expires'] ?? null,
            $params['domain'] ?? null,
            $params['path'] ?? null,
            $params['secure'] ?? null,
            $params['httpOnly'] ?? null,
            $params['sameSite'] ?? null
        );
    }

    /**
     * @psalm-return non-empty-list<null|string>
     */
    private static function splitCookieAttribute(string $attribute): array
    {
        $parts = explode('=', $attribute, 2);
        $parts[1] = $parts[1] ?? null;

        return $parts;
    }
}
