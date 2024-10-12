<?php
declare(strict_types=1);

namespace IfCastle\Configurator;

use IfCastle\Application\Bootloader\BootloaderExecutorInterface;
use IfCastle\Application\Bootloader\BootloaderInterface;
use IfCastle\Application\Bootloader\Builder\ZeroContextInterface;
use IfCastle\Application\Bootloader\Builder\ZeroContextRequiredInterface;
use IfCastle\ServiceManager\RepositoryStorages\RepositoryReaderByScopeInterface;
use IfCastle\ServiceManager\RepositoryStorages\RepositoryReaderInterface;
use IfCastle\ServiceManager\RepositoryStorages\RepositoryWriterInterface;

final class ConfigApplication       extends ConfigIni
                                    implements ZeroContextRequiredInterface, BootloaderInterface
{
    public function __construct() { parent::__construct('!undefined!'); }
    
    
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
        
        $bootloaderExecutor->getBootloaderContext()->getSystemEnvironmentBootBuilder()
            ->bindObject(
                [RepositoryReaderInterface::class, RepositoryReaderByScopeInterface::class],
                new ServiceConfig($appDir)
            )->bindObject(RepositoryWriterInterface::class, new ServiceConfigMutable($appDir));
    }
}