# Changelog

## 5.0 (to be released)

Removed features deprecated in [4.6](#46)

## 4.10

Drop support for php 7.2, 7.3

## 4.7

Deprecations from [4.6](#46) will now trigger an `E_USER_DEPRECATED` deprecation

## 4.6

Deprecated the following methods, use [loginUser() from Symfony 5.1+](https://symfony.com/doc/5.4/testing.html#logging-in-users-authentication) instead of:
- `makeClient`
- `makeAuthenticatedClient`
- `makeClientWithCredentials`
- `loginAs`
- `loginClient`
- `createClientWithParams`

## 4.0

### Removed
 * Drop support of `run:paratest`, if you still need it, define the [`paratest:run` command](https://github.com/liip/LiipFunctionalTestBundle/blob/c732089d9ad32372db4cbee1a5a3c3b53bd40ff6/src/Command/RunParatestCommand.php) in your project
 * Deprecated usage of `environment` property. Use the `static::$env` property instead.

## 3.0
This new major version introduces a number of breaking changes; see the [upgrade guide](UPGRADE-3.0.md) for more details.

### Added
 * Added `makeAuthenticatedClient()` and `makeClientWithCredentials()` methods

### Changed
 * `makeClient()` doesn't accept a boolean or array as the first argument, use
 `makeAuthenticatedClient()` or
 `makeClientWithCredentials($username, $password)` instead

### Removed
 * Removed fixtures loading in favor of https://github.com/liip/LiipTestFixturesBundle

## 2.0
This new major version introduces a number of breaking changes; see the [upgrade guide](UPGRADE-2.0.md) for more details.

### Added
 * Added support for Symfony 4
 * Added `.gitattributes` to make package slimmer 
 * Applied `declare(strict_types=1)` everywhere
 * Added append fixture feature on `LoadFixtues`
 * Added annotation `@DisableDatabaseCache` to disable database cache for a test

### Changed
 * Switched to PSR-4 dir structure with `src` and `tests` subfolders
 * Require at least PHP 7.1
 * Require at least Symfony 3.4
 * Compatibility to DoctrineFixtureBundle at least 3.0
 * Compatibility to Twig at least 2.0
 * Compatibility to JackalopeDoctrineDBAL at least 1.3
 * Switched from `nelmio/alice` to `theofidry/alice-data-fixtures` (which uses `nelmio/alice` 3)
 * The fixtures should be declared as services and tagged with `doctrine.fixture.orm`. It's done automatically if you use [autoconfigure](https://symfony.com/doc/current/service_container.html#service-container-services-load-example)
 * The `runCommand` method now returns a Symfony `CommandTester` instance instead of the command output.

### Removed
 * Drop support for Symfony 2.x
 * Removed HTML5 validation functionality
 * The `WebTestCase::getKernelClass()` function is dropped, since we migrated from `KERNEL_DIR` to `KERNEL_CLASS` constant to support Symfony 4
