<?php

declare(strict_types=1);

namespace IfCastle\Configurator;

class ServiceConfigTest extends ServiceConfigTestCase
{
    public function testServiceConfig(): void
    {
        $file                       = $this->appDir . '/services.ini';

        $config = <<<INI
            [service1]
            ; service configuration
            class = 'ServiceClass1'
            isActive = true
            tags[] = 'tag1'
            tags[] = 'tag2'
            excludeTags[] = 'tag3'
            excludeTags[] = 'tag4'

            [service2]
            ; service configuration
            class = 'ServiceClass2'
            isActive = false
            tags[] = 'tag5'
            tags[] = 'tag6'
            excludeTags[] = 'tag7'
            excludeTags[] = 'tag8'
            INI;


        \file_put_contents($file, $config);

        $serviceConfig              = new ServiceConfig($this->appDir);

        $result                     = $serviceConfig->findServiceConfig('service1');

        $this->assertEquals('ServiceClass1', $result['class']);
        $this->assertTrue($result['isActive']);
        $this->assertEquals(['tag1', 'tag2'], $result['tags']);
        $this->assertEquals(['tag3', 'tag4'], $result['excludeTags']);

        $result                     = $serviceConfig->findServiceConfig('service2');

        $this->assertEquals('ServiceClass2', $result['class']);
        $this->assertFalse($result['isActive']);
        $this->assertEquals(['tag5', 'tag6'], $result['tags']);
        $this->assertEquals(['tag7', 'tag8'], $result['excludeTags']);
    }
}
