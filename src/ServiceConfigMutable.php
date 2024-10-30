<?php

declare(strict_types=1);

namespace IfCastle\Configurator;

use IfCastle\DI\Exceptions\ConfigException;
use IfCastle\Exceptions\RuntimeException;
use IfCastle\OsUtilities\FileSystem\Exceptions\FileIsNotExistException;
use IfCastle\ServiceManager\RepositoryStorages\RepositoryWriterInterface;

class ServiceConfigMutable extends ConfigIniMutable implements RepositoryWriterInterface
{
    use ServiceConfigReaderTrait;

    public function __construct(string $appDir, bool $isReadOnly = false)
    {
        parent::__construct($appDir . '/services.ini', $isReadOnly);
    }

    /**
     * @param string      $packageName
     * @param string      $serviceName
     * @param array       $serviceConfig
     * @param bool        $isActive
     * @param array|null  $includeTags
     * @param array|null  $excludeTags
     * @param string|null $serviceSuffix *
     *
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     * @throws ConfigException
     */
    #[\Override]
    public function addServiceConfig(string      $packageName,
                                     string      $serviceName,
                                     array       $serviceConfig,
                                     bool        $isActive = true,
                                     array|null  $includeTags = null,
                                     array|null  $excludeTags = null,
                                     string|null $serviceSuffix = null
    ): void
    {
        if($this->isExists($serviceName, $serviceSuffix)) {
            throw new \InvalidArgumentException("Service '$serviceName' already exists");
        }
        
        $serviceConfig[self::IS_ACTIVE]     = $isActive;
        $serviceConfig[self::PACKAGE]       = $packageName;
        $serviceConfig[self::NAME]          = $serviceName;

        if ($includeTags !== null) {
            $serviceConfig[self::TAGS]      = $includeTags;
        }

        if ($excludeTags !== null) {
            $serviceConfig[self::EXCLUDE_TAGS] = $excludeTags;
        }

        if($isActive) {
            $conflicts              = $this->checkConflicts($serviceName, $includeTags);
            
            if ($conflicts !== []) {
                $serviceConfig[self::IS_ACTIVE] = false;
            }
        }

        $this->assignServiceConfig($serviceName, $serviceConfig, $serviceSuffix);
    }

    /**
     * @param string $packageName
     * @param string $serviceName
     *
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     */
    #[\Override]
    public function removeServiceConfig(string $packageName, string $serviceName): void
    {
        $this->load();
        
        if(false === array_key_exists($serviceName, $this->data)) {
            return;
        }
        
        $services                   = &$this->data[$serviceName];
        
        if (array_key_exists(self::NAME, $services) && $services[self::PACKAGE] === $packageName) {
            unset($this->data[$serviceName]);
            return;
        }

        foreach ($services as $suffix => $service) {
            if ($service[self::PACKAGE] === $packageName) {
                unset($services[$suffix]);
            }
        }
        
        unset($services);
    }
    
    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     * @throws ConfigException
     */
    #[\Override]
    public function updateServiceConfig(
        string      $packageName,
        string      $serviceName,
        array       $serviceConfig,
        array|null  $includeTags = null,
        array|null  $excludeTags = null,
        string|null $serviceSuffix = null
    ): void {
        
        // First, we try to find the service configuration
        $services                   = $this->data[$serviceName] ?? null;
        
        if ($services === null) {
            throw new \InvalidArgumentException("Service '$serviceName' is not found");
        }
        
        if (array_key_exists(self::NAME, $services)) {
            $services               = [$services];
        }
        
        $service                    = null;
        
        foreach ($services as $suffix => $config) {
            if ($serviceSuffix === $suffix || ($serviceSuffix === null && $config[self::PACKAGE] === $packageName)) {
                $service            = $config;
                break;
            }
        }
        
        if($service === null) {
            throw new \InvalidArgumentException("Service '$serviceName' is not found");
        }
        
        if ($includeTags !== [] && $includeTags !== null) {
            $serviceConfig[self::TAGS] = $includeTags;
        }

        if ($excludeTags !== [] && $excludeTags !== null) {
            $serviceConfig[self::EXCLUDE_TAGS] = $excludeTags;
        }

        $this->assignServiceConfig($serviceName, array_merge($service, $serviceConfig), $serviceSuffix);
    }

    /**
     * @param string $packageName
     * @param string $serviceName
     * @param string $serviceSuffix
     *
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     * @throws ConfigException
     */
    #[\Override]
    public function activateService(string $packageName, string $serviceName, string $serviceSuffix): void
    {
        if ($this->findServiceConfig($serviceName) === null) {
            throw new \InvalidArgumentException("Service '$serviceName' is not found");
        }

        $this->mergeSection($serviceName, [self::IS_ACTIVE => true]);
    }
    
    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws ConfigException
     * @throws \ErrorException
     */
    #[\Override]
    public function deactivateService(string $packageName, string $serviceName, string $serviceSuffix): void
    {
        if ($this->findServiceConfig($serviceName) === null) {
            throw new \InvalidArgumentException("Service '$serviceName' is not found");
        }

        $this->mergeSection($serviceName, [self::IS_ACTIVE => false]);
    }

    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws ConfigException
     * @throws \ErrorException
     */
    #[\Override]
    public function changeServiceTags(string     $packageName,
                                      string     $serviceName,
                                      string     $serviceSuffix,
                                      array|null $includeTags = null,
                                      array|null $excludeTags = null
    ): void
    {
        if ($this->findServiceConfig($serviceName) === null) {
            throw new \InvalidArgumentException("Service '$serviceName' is not found");
        }

        $data                       = [];

        if ($includeTags !== [] && $includeTags !== null) {
            $data[self::TAGS]       = $includeTags;
        }

        if ($excludeTags !== [] && $excludeTags !== null) {
            $data[self::EXCLUDE_TAGS] = $excludeTags;
        }

        $this->mergeSection($serviceName, $data);
    }

    #[\Override]
    public function saveRepository(): void
    {
        $this->save();
    }
    
    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     */
    protected function findServiceConfigByNameAndSuffix(string $serviceName, ?string $serviceSuffix = null): array|null
    {
        $this->load();
        $services                   = $this->data[$serviceName] ?? null;

        if ($services === null) {
            return null;
        }
        
        if (array_key_exists(self::NAME, $services)) {
            $services               = [$services];
        }
        
        if($services === []) {
            return null;
        }
        
        if ($serviceSuffix === null) {
            return $services[array_key_first($services)];
        }
        
        return $services[$serviceSuffix] ?? null;
    }
    
    protected function isExists(string $serviceName, ?string $serviceSuffix = null): bool
    {
        $this->load();
        $services                   = $this->data[$serviceName] ?? null;
        
        if ($services === null) {
            return false;
        }
        
        if (array_key_exists(self::NAME, $services)) {
            $services               = [$services];
        }
        
        if ($serviceSuffix === null) {
            return true;
        }
        
        return array_key_exists($serviceSuffix, $services);
    }
    
    /**
     * The method checks that the new service to be added does not conflict
     * with already existing services of the same name,
     * ensuring that only one of them will be loaded.
     *
     * This means that the active services' IncludeTags must not overlap.
     *
     * @param string    $serviceName
     * @param string[]  $includeTags
     *
     * @return array<array{0: string, 1: string, 2: array<string>}>
     */
    protected function checkConflicts(string $serviceName, array $includeTags): array
    {
        $this->load();
        $services                   = $this->data[$serviceName] ?? null;
        
        if ($services === null) {
            return [];
        }
        
        if (array_key_exists(self::NAME, $services)) {
            $services               = [$services];
        }
        
        $conflicts                  = [];
        
        foreach ($services as $servicePrefix => $service) {
            $serviceIncludeTags     = $service[self::TAGS] ?? [];
            $intersect              = array_intersect($includeTags, $serviceIncludeTags);
            
            if ($intersect !== []) {
                $conflicts[]        = [$service[self::PACKAGE], $servicePrefix, $intersect];
            }
        }
        
        return $conflicts;
    }
    
    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws ConfigException
     * @throws \ErrorException
     */
    protected function assignServiceConfig(string $serviceName, array $config, string $suffix = null): void
    {
        if(array_key_exists($serviceName, $this->data) && array_key_exists(self::NAME, $this->data[$serviceName])) {
            $this->data[$serviceName] = [$this->data[$serviceName]];
        }
        
        if(array_key_exists($serviceName, $this->data) && $suffix === null) {
            $suffix                 = \count($this->data[$serviceName]);
        }
        
        if($suffix !== null) {
            $serviceName            .= $suffix;
        }
        
        $this->set($serviceName, $config);
    }
    
    protected function afterBuild(string $content): string
    {
        $at                         = \date('Y-m-d H:i:s');
        $comment                    = <<<INI
            ; ================================================================
            ; This file is generated by the IfCastle Configurator
            ; at $at
            ; Do not edit this file manually!
            ; ================================================================
            INI;
        return $comment . $content;
    }
}
