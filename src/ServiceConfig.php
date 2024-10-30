<?php

declare(strict_types=1);

namespace IfCastle\Configurator;

use IfCastle\ServiceManager\RepositoryStorages\RepositoryReaderByTagsInterface;
use IfCastle\ServiceManager\RepositoryStorages\RepositoryReaderInterface;

/**
 * Ini configuration reader|writer for services.
 *
 * The service registry contains a list of all system services in the format:
 * [service_name.number]
 * package=""
 * _service_name_=""
 * ...
 *
 * The reason for this structure is that
 * different packages can define services with the same name
 * but with different implementations for various use cases.
 *
 */
class ServiceConfig extends ConfigIni implements RepositoryReaderInterface, RepositoryReaderByTagsInterface
{
    use ServiceConfigReaderTrait;

    public function __construct(string $appDir)
    {
        parent::__construct($appDir . '/services.ini');
    }
}
