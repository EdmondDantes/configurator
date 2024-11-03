<?php

declare(strict_types=1);

namespace IfCastle\Configurator;

use IfCastle\DI\Exceptions\ConfigException;
use IfCastle\Exceptions\RuntimeException;
use IfCastle\OsUtilities\FileSystem\Exceptions\FileIsNotExistException;
use IfCastle\ServiceManager\RepositoryStorages\ServiceCollectionInterface;
use IfCastle\ServiceManager\RepositoryStorages\ServiceCollectionWriterInterface;

class ServiceConfigMutable extends ConfigIniMutable implements ServiceCollectionWriterInterface
{
    use ServiceConfigReaderTrait;

    public function __construct(string $appDir, bool $isReadOnly = false)
    {
        parent::__construct($appDir . '/services.ini', $isReadOnly);
    }

    /**
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
    ): void {
        if ($this->isLoaded === false) {
            $this->load();
            $this->normalizeDataAfterLoad();
        }

        if ($this->isExists($serviceName, $serviceSuffix)) {
            throw new \InvalidArgumentException("Service '$serviceName' already exists");
        }

        $serviceConfig[ServiceCollectionInterface::IS_ACTIVE]     = $isActive;
        $serviceConfig[ServiceCollectionInterface::PACKAGE]       = $packageName;
        $serviceConfig[ServiceCollectionInterface::NAME]          = $serviceName;

        if ($includeTags !== null) {
            $serviceConfig[ServiceCollectionInterface::TAGS]      = $includeTags;
        }

        if ($excludeTags !== null) {
            $serviceConfig[ServiceCollectionInterface::EXCLUDE_TAGS] = $excludeTags;
        }

        if ($isActive) {
            $conflicts              = $this->checkConflicts($serviceName, $includeTags);

            if ($conflicts !== []) {
                $serviceConfig[ServiceCollectionInterface::IS_ACTIVE] = false;
            }
        }

        $this->assignServiceConfig($serviceName, $serviceConfig, $serviceSuffix);
    }

    /**
     *
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     */
    #[\Override]
    public function removeServiceConfig(string $packageName, string $serviceName): void
    {
        if ($this->isLoaded === false) {
            $this->load();
            $this->normalizeDataAfterLoad();
        }

        if (false === \array_key_exists($serviceName, $this->data)) {
            return;
        }

        $services                   = &$this->data[$serviceName];

        foreach ($services as $suffix => $service) {
            if ($service[ServiceCollectionInterface::PACKAGE] === $packageName) {
                unset($services[$suffix]);
            }
        }

        unset($services);
    }

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

        $foundedServices            = [];
        $firstServiceSuffix         = null;

        foreach ($services as $suffix => $config) {

            if ($serviceSuffix === null && $config[ServiceCollectionInterface::PACKAGE] === $packageName) {
                $foundedServices[] = $config;

                if ($firstServiceSuffix === null) {
                    $firstServiceSuffix = (string) $suffix;
                }

            } elseif ($serviceSuffix === $suffix) {
                $foundedServices[] = $config;
                break;
            }
        }

        if ($foundedServices === []) {
            throw new \InvalidArgumentException("Service '$serviceName' is not found");
        }

        if (\count($foundedServices) > 1) {
            throw new \InvalidArgumentException("Service '$serviceName' has multiple configurations. Please specify the service suffix!");
        }

        if ($serviceSuffix === null) {
            $serviceSuffix          = $firstServiceSuffix;
        }

        $service                    = $foundedServices[0];

        if ($includeTags !== [] && $includeTags !== null) {
            $serviceConfig[ServiceCollectionInterface::TAGS] = $includeTags;
        }

        if ($excludeTags !== [] && $excludeTags !== null) {
            $serviceConfig[ServiceCollectionInterface::EXCLUDE_TAGS] = $excludeTags;
        }

        $this->assignServiceConfig($serviceName, \array_merge($service, $serviceConfig), $serviceSuffix);
    }

    /**
     *
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     * @throws ConfigException
     */
    #[\Override]
    public function activateService(string $packageName, string $serviceName, string $serviceSuffix): void
    {
        $serviceConfig              = &$this->findRefToServiceConfigByNameAndSuffix($serviceName, $serviceSuffix);

        if ($serviceConfig === null) {
            throw new \InvalidArgumentException("Service '$serviceName.$serviceSuffix' is not found");
        }

        $serviceConfig[self::IS_ACTIVE] = true;
        unset($serviceConfig);
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
        $serviceConfig              = &$this->findRefToServiceConfigByNameAndSuffix($serviceName, $serviceSuffix);

        if ($serviceConfig === null) {
            throw new \InvalidArgumentException("Service '$serviceName.$serviceSuffix' is not found");
        }

        $serviceConfig[self::IS_ACTIVE] = false;
        unset($serviceConfig);
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
    ): void {
        $serviceConfig              = &$this->findRefToServiceConfigByNameAndSuffix($serviceName, $serviceSuffix);

        if ($serviceConfig === null) {
            throw new \InvalidArgumentException("Service '$serviceName.$serviceSuffix' is not found");
        }

        if ($includeTags !== [] && $includeTags !== null) {
            $serviceConfig[self::TAGS]       = $includeTags;
        }

        if ($excludeTags !== [] && $excludeTags !== null) {
            $serviceConfig[self::EXCLUDE_TAGS] = $excludeTags;
        }

        unset($serviceConfig);
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
    protected function &findRefToServiceConfigByNameAndSuffix(string $serviceName, ?string $serviceSuffix = null): array|null
    {
        if ($this->isLoaded === false) {
            $this->load();
            $this->normalizeDataAfterLoad();
        }

        $nullRef                    = null;

        if (\array_key_exists($serviceName, $this->data) === false) {
            return $nullRef;
        }

        $services                   = & $this->data[$serviceName];

        if ($services === []) {
            return $nullRef;
        }

        if ($serviceSuffix === null) {
            return $services[\array_key_first($services)];
        }

        if (\array_key_exists($serviceSuffix, $services)) {
            return $services[$serviceSuffix];
        }

        return $nullRef;
    }

    protected function isExists(string $serviceName, ?string $serviceSuffix = null): bool
    {
        if ($this->isLoaded === false) {
            $this->load();
            $this->normalizeDataAfterLoad();
        }

        $services                   = $this->data[$serviceName] ?? null;

        if ($services === null || $services === []) {
            return false;
        }

        if ($serviceSuffix === null) {
            return true;
        }

        return \array_key_exists($serviceSuffix, $services);
    }

    /**
     * The method checks that the new service to be added does not conflict
     * with already existing services of the same name,
     * ensuring that only one of them will be loaded.
     *
     * This means that the active services' IncludeTags must not overlap.
     *
     * @param string[]  $includeTags
     *
     * @return array<array{0: string, 1: string, 2: array<string>}>
     */
    protected function checkConflicts(string $serviceName, array $includeTags): array
    {
        if ($this->isLoaded === false) {
            $this->load();
            $this->normalizeDataAfterLoad();
        }

        $services                   = $this->data[$serviceName] ?? null;

        if ($services === null) {
            return [];
        }

        $conflicts                  = [];

        foreach ($services as $servicePrefix => $service) {
            $serviceIncludeTags     = $service[self::TAGS] ?? [];
            $intersect              = \array_intersect($includeTags, $serviceIncludeTags);

            if ($intersect !== []) {
                $conflicts[]        = [$service[self::PACKAGE], $servicePrefix, $intersect];
            }
        }

        return $conflicts;
    }

    protected function assignServiceConfig(string $serviceName, array $config, ?string $suffix = null): void
    {
        if ($suffix === null) {
            $this->data[$serviceName][] = $config;
        } else {
            $this->data[$serviceName][$suffix] = $config;
        }
    }

    #[\Override]
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
