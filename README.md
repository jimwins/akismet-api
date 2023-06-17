# PHP Akismet API

This is a simple PHP library for accessing the Akismet API without any framework dependencies.
Spam checking does require a PSR-7 ServerRequest but one can easily be faked using GuzzleHttp.

[![Source](https://img.shields.io/badge/source-jimwins/akismet-api-blue.svg?style=flat-square)](https://github.com/jimwins/akismet-api) [![Build Status](https://img.shields.io/travis/jimwins/akismet-api.svg?style=flat-square)](https://travis-ci.org/jimwins/akismet-api) [![Total Downloads](https://img.shields.io/packagist/dt/jimwins/akismet-api.svg?style=flat-square)](https://packagist.org/packages/jimwins/akismet-api) [![Latest Stable Version](https://img.shields.io/packagist/v/jimwins/akismet-api.svg?style=flat-square)](https://packagist.org/packages/jimwins/akismet-api)


## Installation
This is installable and autoloadable via Composer as [jimwins/akismet-api](https://packagist.org/packages/jimwins/akismet-api). If you aren't familiar with the Composer Dependency Manager for PHP, [you should read this first](https://getcomposer.org/doc/00-intro.md).

```bash
$ composer require jimwins/akismet-api --prefer-dist
```

## Sample Usage

``` php
<?php
$api = new \Akismet\API("{Your Akismet API Key}", "{{ your site/blog URL }}");

if (!$api->verifyKey()) {
  die("That API key could not be verified.");
}

$api->commentCheck($values, $request);

$api->submitSpam($values);

$api->submitHam($values);

$api->keySites();

$api->usageLimit();
```

### Unit Testing

``` bash
$ vendor/bin/phpunit
```
