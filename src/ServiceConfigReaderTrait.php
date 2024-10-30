<?php

declare(strict_types=1);

namespace IfCastle\Configurator;

use IfCastle\Exceptions\RuntimeException;
use IfCastle\OsUtilities\FileSystem\Exceptions\FileIsNotExistException;

trait ServiceConfigReaderTrait
{
    public const string NAME        = '_service_name_';
    public const string IS_ACTIVE   = 'isActive';
    public const string PACKAGE     = 'package';
    public const string TAGS        = 'tags';
    public const string EXCLUDE_TAGS = 'excludeTags';

    abstract protected function load(): void;
    protected array $data = [];
    
    /**
     * Returns all services configuration with duplicates.
     *
     * @return array<array<array<mixed>>>
     */
    #[\Override]
    public function getServicesConfigAll(): array
    {
        $this->load();
        return $this->data;
    }
    
    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     */
    #[\Override]
    public function getServicesConfig(): array
    {
        $this->load();
        
        $services                   = [];
        
        foreach ($this->data as $config) {
            if(array_key_exists(self::NAME, $config)) {
                $services[$config[self::NAME]] = $config;
            }
        }
        
        return $this->data;
    }
    
    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     */
    #[\Override]
    public function findServiceConfigByTags(string $serviceName, string ...$tags): array|null
    {
        $this->load();
        
        $services                   = $this->data[$serviceName] ?? null;

        if ($services === null) {
            return null;
        }
        
        if(array_key_exists(self::NAME, $services)) {
            $services               = [$services];
        }
        
        foreach ($services as $serviceConfig) {
            // If any $scopesIncluded item is in $scopes, then return the serviceConfig
            // or if any $scopesExcluded item is in $scopes, then return null
            if (\count(\array_intersect($serviceConfig[self::TAGS] ?? [], $tags)) > 0
               && \count(\array_intersect($serviceConfig[self::EXCLUDE_TAGS] ?? [], $tags)) === 0) {
                return $serviceConfig;
            }
        }
        
        return null;
    }
    
    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     */
    #[\Override]
    public function findServiceConfig(string $serviceName): array|null
    {
        $this->load();
        
        $services = $this->data[$serviceName] ?? null;
        
        if ($services === null || $services === []) {
            return null;
        }
        
        if(array_key_exists(self::NAME, $services)) {
            return $services;
        } else {
            return $services[array_key_first($services)];
        }
    }
    
    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     */
    #[\Override]
    public function getServicesConfigByTags(string ...$tags): array
    {
        $this->load();

        $servicesConfig             = [];

        foreach ($this->data as $service => $configs) {

            if(array_key_exists(self::NAME, $configs)) {
                $configs            = [$configs];
            }
            
            foreach ($configs as $config) {
                // If any $scopesIncluded item is in $scopes, then add the service to $servicesConfig
                // or if any $scopesExcluded item is in $scopes, then skip the service
                if (\count(\array_intersect($config[self::TAGS] ?? [], $tags)) > 0
                   && \count(\array_intersect($config[self::EXCLUDE_TAGS] ?? [], $tags)) === 0) {
                    $servicesConfig[$service] = $config;
                }
            }
        }

        return $servicesConfig;
    }
    
    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     */
    #[\Override]
    public function findServicesConfigByPackage(string $packageName): array
    {
        $this->load();
        
        $servicesConfig             = [];
        
        foreach ($this->data as $service => $configs) {
            
            if(array_key_exists(self::NAME, $configs)) {
                $configs            = [$configs];
            }
            
            foreach ($configs as $config) {
                if ($config[self::PACKAGE] === $packageName) {
                    $servicesConfig[$service] = $config;
                }
            }
        }
        
        return $servicesConfig;
    }
}
