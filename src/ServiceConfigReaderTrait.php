<?php

declare(strict_types=1);

namespace IfCastle\Configurator;

use IfCastle\ServiceManager\RepositoryStorages\ServiceCollectionInterface;

trait ServiceConfigReaderTrait
{
    public const string NAME        = '_service_name_';
    public const string IS_ACTIVE   = 'isActive';
    public const string PACKAGE     = 'package';
    public const string TAGS        = 'tags';
    public const string EXCLUDE_TAGS = 'excludeTags';

    abstract protected function load(): void;
    
    /**
     * @var array<array<array<mixed>>>
     */
    protected array $data           = [];
    
    protected bool $isLoaded        = false;
    
    public function getServiceCollection(
        string|null $serviceName = null,
        string|null $packageName = null,
        string|null $suffix = null,
        array       $tags = []
    ): array
    {
        if($this->isLoaded === false) {
            $this->load();
            $this->normalizeDataAfterLoad();
        }
        
        if($serviceName === null && $packageName === null && $suffix === null && $tags === []) {
            return $this->data;
        }
        
        $collection                 = [];
        
        if($serviceName !== null && array_key_exists($serviceName, $this->data)) {
            $set                    = [$serviceName => $this->data[$serviceName]];
        } else {
            $set                    =& $this->data;
        }
        
        foreach ($set as $service => $implementations) {
            foreach ($implementations as $serviceSuffix => $serviceConfig) {
                
                if(($suffix !== null && $suffix !== (string)$serviceSuffix)
                   || ($packageName !== null && $serviceConfig[ServiceCollectionInterface::PACKAGE] !== $packageName)
                   || ($tags !== [] && \count(\array_intersect($serviceConfig[ServiceCollectionInterface::TAGS] ?? [], $tags)) === 0)
                   || \count(\array_intersect($serviceConfig[ServiceCollectionInterface::EXCLUDE_TAGS] ?? [], $tags)) > 0) {
                    continue;
                }
                
                $collection[$service][(string)$serviceSuffix] = $serviceConfig;
            }
        }
        
        return $collection;
    }
    
    protected function normalizeDataAfterLoad(): void
    {
        $this->isLoaded             = true;
        
        foreach ($this->data as $service => $configs) {
            if(array_key_exists(ServiceCollectionInterface::NAME, $configs)) {
                $this->data[$service] = [$configs];
            }
        }
    }
}
