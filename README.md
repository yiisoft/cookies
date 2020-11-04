<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://github.com/yiisoft.png" height="100px">
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

The package helps in working with HTTP cookies in a [PSR-7](https://www.php-fig.org/psr/psr-7/) environment:
 
- provides a handy abstraction representing a cookie
- allows dealing with many cookies at once
- forms and adds `Set-Cookie` headers to response

## Installation

The package could be installed with composer:

```
composer install yiisoft/cookies
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

See [Yii guide to cookies](https://github.com/yiisoft/docs/blob/master/guide/en/runtime/cookies.md) for more info.

## Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```php
./vendor/bin/phpunit
```

## Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```php
./vendor/bin/infection
```

## Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```php
./vendor/bin/psalm
```
