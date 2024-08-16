<?php
declare(strict_types=1);

namespace IfCastle\Configurator;

use IfCastle\ServiceManager\RepositoryStorages\RepositoryReaderByScopeInterface;
use IfCastle\ServiceManager\RepositoryStorages\RepositoryReaderInterface;

class ServiceConfig                 extends ConfigIni
                                    implements RepositoryReaderInterface, RepositoryReaderByScopeInterface
{
    public function __construct(string $appDir)
    {
        parent::__construct($appDir . '/services.ini');
    }
    
    #[\Override]
    public function getServicesConfig(): array
    {
        $this->load();
        return $this->data;
    }
    
    #[\Override]
    public function getServicesConfigByScope(string ...$scopes): array
    {
        $this->load();
        
        $servicesConfig             = [];
        
        foreach($this->data as $service => $config) {
            
            // If any $scopesIncluded item is in $scopes, then add the service to $servicesConfig
            // or if any $scopesExcluded item is in $scopes, then skip the service
            if(count(array_intersect($config['scopes'] ?? [], $scopes)) > 0
            || count(array_intersect($config['scopesExcluded'] ?? [], $scopes)) === 0) {
                $servicesConfig[$service] = $config;
            }
        }
        
        return $servicesConfig;
    }
}