<?php
declare(strict_types=1);

namespace IfCastle\Configurator;

trait ServiceConfigReaderTrait
{
    abstract protected function load(): void;
    protected array $data = [];
    
    #[\Override]
    public function getServicesConfig(): array
    {
        $this->load();
        return $this->data;
    }
    
    #[\Override]
    public function findServiceConfigByScope(string $serviceName, string ...$scopes): array|null
    {
        $this->load();
        
        $serviceConfig              = $this->data[$serviceName] ?? null;
        
        if($serviceConfig === null) {
            return null;
        }
        
        // If any $scopesIncluded item is in $scopes, then return the serviceConfig
        // or if any $scopesExcluded item is in $scopes, then return null
        if(count(array_intersect($serviceConfig['scopes'] ?? [], $scopes)) > 0
           && count(array_intersect($serviceConfig['scopesExcluded'] ?? [], $scopes)) === 0) {
            return $serviceConfig;
        }
        
        return null;
    }
    
    #[\Override]
    public function findServiceConfig(string $serviceName): array|null
    {
        $this->load();
        
        return $this->data[$serviceName] ?? null;
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
               && count(array_intersect($config['scopesExcluded'] ?? [], $scopes)) === 0) {
                $servicesConfig[$service] = $config;
            }
        }
        
        return $servicesConfig;
    }
}