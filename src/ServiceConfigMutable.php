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
        
        if(array_key_exists($serviceName, $this->data) && array_key_exists(self::NAME, $this->data[$serviceName])) {
            $this->data[$serviceName] = [$this->data[$serviceName]];
        }
        
        if(array_key_exists($serviceName, $this->data) && $serviceSuffix === null) {
            $serviceSuffix          = \count($this->data[$serviceName]);
        }
        
        if($serviceSuffix !== null) {
            $serviceName            .= $serviceSuffix;
        }
        
        if($serviceSuffix !== null) {
            $serviceName            .= $serviceSuffix;
        }
        
        $this->set($serviceName, $serviceConfig);
    }

    /**
     * @param string $packageName
     * @param string $serviceName
     *
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws ConfigException
     * @throws \ErrorException
     */
    #[\Override]
    public function removeServiceConfig(string $packageName, string $serviceName): void
    {
        $this->remove($serviceName);
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
        if ($includeTags !== [] && $includeTags !== null) {
            $serviceConfig[self::TAGS] = $includeTags;
        }

        if ($excludeTags !== [] && $excludeTags !== null) {
            $serviceConfig[self::EXCLUDE_TAGS] = $excludeTags;
        }

        $this->mergeSection($serviceName, $serviceConfig);
    }

    /**
     * @param string $packageName
     * @param string $serviceName
     * @param string $serviceSuffix *
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
    
    protected function isExists(string $serviceName, ?string $servicePrefix = null): bool
    {
        $this->load();
        $services                   = $this->data[$serviceName] ?? null;
        
        if ($services === null) {
            return false;
        }
        
        if (array_key_exists(self::NAME, $services)) {
            $services               = [$services];
        }
        
        if ($servicePrefix === null) {
            return true;
        }
        
        return array_key_exists($servicePrefix, $services);
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
