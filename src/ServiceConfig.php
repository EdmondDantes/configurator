<?php
declare(strict_types=1);

namespace IfCastle\Configurator;

use IfCastle\ServiceManager\RepositoryStorages\RepositoryReaderInterface;

class ServiceConfig                 extends ConfigIni
                                    implements RepositoryReaderInterface
{
    public function __construct(string $appDir)
    {
        parent::__construct($appDir . '/services.ini');
    }
    
    public function getServicesConfig(): array
    {
        $this->load();
        return $this->data;
    }
}