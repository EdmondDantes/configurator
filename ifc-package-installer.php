<?php
declare(strict_types=1);

use IfCastle\Application\Bootloader\BootManager\BootManagerInterface;
use IfCastle\Application\Bootloader\BootManager\BootManagerApplication;
use IfCastle\Application\Installer\PackageInstallerInterface;
use IfCastle\Application\Bootloader\Builder\ZeroContextInterface;

return new readonly class implements PackageInstallerInterface
{
    public function __construct(private BootManagerInterface $bootManager, ZeroContextInterface $zeroContext) {}
    
    #[\Override]
    public function install(): void
    {
        $this->bootManager->addBootloader(BootManagerApplication::CONFIGURATOR, [
            \IfCastle\Configurator\ConfigApplication::class
        ]);
    }
    
    #[\Override]
    public function update(): void
    {
    }
    
    #[\Override]
    public function uninstall(): void
    {
        $this->bootManager->removeBootloader(BootManagerApplication::CONFIGURATOR);
    }
};