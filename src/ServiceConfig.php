<?php
declare(strict_types=1);

namespace IfCastle\Configurator;

use IfCastle\ServiceManager\RepositoryStorages\RepositoryReaderByScopeInterface;
use IfCastle\ServiceManager\RepositoryStorages\RepositoryReaderInterface;

class ServiceConfig                 extends ConfigIni
                                    implements RepositoryReaderInterface, RepositoryReaderByScopeInterface
{
    use ServiceConfigReaderTrait;
    
    public function __construct(string $appDir)
    {
        parent::__construct($appDir . '/services.ini');
    }
}