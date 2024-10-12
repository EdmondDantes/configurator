<?php
declare(strict_types=1);

namespace IfCastle\Configurator;

use IfCastle\ServiceManager\RepositoryStorages\RepositoryReaderByScopeInterface;
use IfCastle\ServiceManager\RepositoryStorages\RepositoryWriterInterface;

class ServiceConfigMutable          extends ConfigIniMutable
                                    implements RepositoryWriterInterface, RepositoryReaderByScopeInterface
{
    use ServiceConfigReaderTrait;
    
    #[\Override]
    public function addServiceConfig(string $serviceName, array $serviceConfig, array $scopes = []): void
    {
        if(array_key_exists('isActive', $serviceConfig) === false) {
            $serviceConfig['isActive'] = false;
        }
        
        if(array_key_exists('scopes', $serviceConfig) === false) {
            $serviceConfig['scopes'] = $scopes;
        }
        
        $this->set($serviceName, $serviceConfig);
    }
    
    #[\Override]
    public function removeServiceConfig(string $serviceName): void
    {
        $this->remove($serviceName);
    }
    
    #[\Override]
    public function updateServiceConfig(
        string $serviceName,
        array  $serviceConfig,
        array  $scopes = []
    ): void
    {
        if($scopes !== []) {
            $serviceConfig['scopes'] = $scopes;
        }
        
        $this->mergeSection($serviceName, $serviceConfig);
    }
    
    #[\Override]
    public function activateService(string $serviceName): void
    {
        if($this->findServiceConfig($serviceName) === null) {
            throw new \InvalidArgumentException("Service '$serviceName' is not found");
        }
        
        $this->mergeSection($serviceName, ['is_active' => true]);
    }
    
    #[\Override]
    public function deactivateService(string $serviceName): void
    {
        if($this->findServiceConfig($serviceName) === null) {
            throw new \InvalidArgumentException("Service '$serviceName' is not found");
        }
        
        $this->mergeSection($serviceName, ['is_active' => false]);
    }
    
    #[\Override]
    public function changeServiceScope(string $serviceName, array $scopes): void
    {
        if($this->findServiceConfig($serviceName) === null) {
            throw new \InvalidArgumentException("Service '$serviceName' is not found");
        }
        
        $this->mergeSection($serviceName, ['scopes' => $scopes]);
    }
    
    protected function afterBuild(string $content): string
    {
        $at                         = date('Y-m-d H:i:s');
        $comment                    = <<<INI
; ================================================================
; This file is generated by the IfCastle Configurator
; at $at
; Do not edit this file manually!
; ================================================================
INI;
        return $comment.$content;
    }
}