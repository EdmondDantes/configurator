<?php
declare(strict_types=1);

namespace IfCastle\Configurator;

trait ServiceConfigReaderTrait
{
    public const string IS_ACTIVE = 'isActive';
    public const string TAGS = 'tags';
    public const string EXCLUDE_TAGS = 'excludeTags';
    
    abstract protected function load(): void;
    protected array $data = [];
    
    #[\Override]
    public function getServicesConfig(): array
    {
        $this->load();
        return $this->data;
    }
    
    #[\Override]
    public function findServiceConfigByTags(string $serviceName, string ...$tags): array|null
    {
        $this->load();
        
        $serviceConfig              = $this->data[$serviceName] ?? null;
        
        if($serviceConfig === null) {
            return null;
        }
        
        // If any $scopesIncluded item is in $scopes, then return the serviceConfig
        // or if any $scopesExcluded item is in $scopes, then return null
        if(count(array_intersect($serviceConfig[self::TAGS] ?? [], $tags)) > 0
           && count(array_intersect($serviceConfig[self::EXCLUDE_TAGS] ?? [], $tags)) === 0) {
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
    public function getServicesConfigByTags(string ...$tags): array
    {
        $this->load();
        
        $servicesConfig             = [];
        
        foreach($this->data as $service => $config) {
            
            // If any $scopesIncluded item is in $scopes, then add the service to $servicesConfig
            // or if any $scopesExcluded item is in $scopes, then skip the service
            if(count(array_intersect($config[self::TAGS] ?? [], $tags)) > 0
               && count(array_intersect($config[self::EXCLUDE_TAGS] ?? [], $tags)) === 0) {
                $servicesConfig[$service] = $config;
            }
        }
        
        return $servicesConfig;
    }
}