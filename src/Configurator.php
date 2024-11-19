<?php

declare(strict_types=1);

namespace IfCastle\Configurator;

use IfCastle\Application\Bootloader\BootloaderExecutorInterface;
use IfCastle\Application\Bootloader\BootloaderInterface;
use IfCastle\Application\Bootloader\BootManager\MainConfigAppenderInterface;
use IfCastle\Application\Bootloader\Builder\ZeroContextInterface;
use IfCastle\Application\Bootloader\Builder\ZeroContextRequiredInterface;
use IfCastle\DI\ConfigInterface;
use IfCastle\ServiceManager\RepositoryStorages\RepositoryReaderInterface;
use IfCastle\ServiceManager\RepositoryStorages\ServiceCollectionInterface;
use IfCastle\ServiceManager\RepositoryStorages\ServiceCollectionWriterInterface;

final class Configurator extends ConfigIni implements ZeroContextRequiredInterface, BootloaderInterface
{
    public function __construct()
    {
        parent::__construct('!undefined!');
    }

    #[\Override]
    public function setZeroContext(ZeroContextInterface $zeroContext): static
    {
        $this->file                 = $zeroContext->getApplicationDirectory() . '/main.ini';
        return $this;
    }

    #[\Override]
    public function buildBootloader(BootloaderExecutorInterface $bootloaderExecutor): void
    {
        $appDir                     = $bootloaderExecutor->getBootloaderContext()->getApplicationDirectory();
        $builder                    = $bootloaderExecutor->getBootloaderContext()->getSystemEnvironmentBootBuilder();

        $builder
            ->bindObject(ConfigInterface::class, new ConfigMain($appDir), isThrow: false)
            ->bindObject([RepositoryReaderInterface::class, ServiceCollectionInterface::class],
                new ServiceConfig($appDir), isThrow: false
            )
            ->bindObject(ServiceCollectionWriterInterface::class, new ServiceConfigWriter($appDir), isThrow: false)
            ->bindObject(MainConfigAppenderInterface::class, new ConfigMainAppender($appDir), isThrow: false);
    }
}
