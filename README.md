<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Cookies</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/cookies/v/stable.png)](https://packagist.org/packages/yiisoft/cookies)
[![Total Downloads](https://poser.pugx.org/yiisoft/cookies/downloads.png)](https://packagist.org/packages/yiisoft/cookies)
[![Build status](https://github.com/yiisoft/cookies/workflows/build/badge.svg)](https://github.com/yiisoft/cookies/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/cookies/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/cookies/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/cookies/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/cookies/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fcookies%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/cookies/master)
[![static analysis](https://github.com/yiisoft/cookies/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/cookies/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/cookies/coverage.svg)](https://shepherd.dev/github/yiisoft/cookies)

The package helps in working with HTTP cookies in a [PSR-7](https://www.php-fig.org/psr/psr-7/) environment:

- provides a handy abstraction representing a cookie
- allows dealing with many cookies at once
- forms and adds `Set-Cookie` headers to response
- signs a cookie to prevent its value from being tampered

## Requirements

- PHP 7.4 or higher.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/cookies --prefer-dist
```

## General usage

Adding a cookie to response:

```php
$cookie = (new \Yiisoft\Cookies\Cookie('cookieName', 'value'))
    ->withPath('/')
    ->withDomain('yiiframework.com')
    ->withHttpOnly(true)
    ->withSecure(true)
    ->withSameSite(\Yiisoft\Cookies\Cookie::SAME_SITE_STRICT)
    ->withMaxAge(new \DateInterval('P7D'));

$response = $cookie->addToResponse($response);
```

Modifying response cookies to be sent:

```php
$cookies = \Yiisoft\Cookies\CookieCollection::fromResponse($response);
$cookies->expire('login');
$response = $cookies->setToResponse($response);
```

Getting request cookies:

```php
$cookies = \Yiisoft\Cookies\CookieCollection::fromArray($request->getCookieParams());
```

Signing a cookie to prevent its value from being tampered:

```php
$cookie = new \Yiisoft\Cookies\Cookie('identity', 'identityValue');

// The secret key used to sign and validate cookies.
$key = '0my1xVkjCJnD_q1yr6lUxcAdpDlTMwiU';
$signer = new \Yiisoft\Cookies\CookieSigner($key);

// Prefixes unique hash based on the value of the cookie.
$signedCookie = $signer->sign($cookie);

// Validate and get back the cookie with clean value.
$cookie = $signer->validate($signedCookie);

// Before validation, check if the cookie is signed.
if ($signer->isSigned($cookie)) {
    $cookie = $signer->validate($cookie);
}
```

See [Yii guide to cookies](https://github.com/yiisoft/docs/blob/master/guide/en/runtime/cookies.md) for more info.

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework with
[Infection Static Analysis Plugin](https://github.com/Roave/infection-static-analysis-plugin). To run it:

```shell
./vendor/bin/roave-infection-static-analysis-plugin
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

## License

The Yii Cookies is free software. It is released under the terms of the BSD License. Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
