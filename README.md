# Configurator [![PHP Composer](https://github.com/EdmondDantes/configurator-ini/actions/workflows/php.yml/badge.svg)](https://github.com/EdmondDantes/configurator-ini/actions/workflows/php.yml)

A simple ini-configurator for the `IfCastle` framework.
Implement the main application configuration and service configuration.

The package implements the following interfaces:

* `ConfigInterface`
* `RepositoryReaderInterface`
* `ServiceCollectionInterface`
* `ServiceCollectionWriterInterface`

## Installation

> This package must be installed within the **IfCastle** application environment, 
> i.e., in `ifcastle/package-installer`, 
> if you want the dependencies to be correctly defined.

```bash
composer require ifcastle/configurator-ini
```

## Usage

The package defines two ini files:

* the main application configuration file: `main.ini`
* the service registry: `services.ini`

All files must be located in the root directory of the project.

## Syntax

This package uses the `ini_parse` function under the hood in data type mode 
and additionally converts sections of the type [section.key] into a nested array.

Automatic replacement for `ENV` variables will also work, i.e., syntax with "%":
   
```ini
[database]
host = %DB_HOST%
port = %DB_PORT%
user = %DB_USER%
password = %DB_PASSWORD%
```

See also the [ini_parse](https://www.php.net/manual/en/function.parse-ini-file.php) function.