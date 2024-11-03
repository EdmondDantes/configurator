<?php

declare(strict_types=1);

namespace IfCastle\Configurator;

class ServiceConfigTest extends ServiceConfigTestCase
{
    public function testServiceConfig(): void
    {
        $file                       = $this->appDir . '/services.ini';

        $config = <<<INI
            [service1.0]
            _service_name_ = 'service1'
            ; service configuration
            class = 'ServiceClass1'
            isActive = true
            tags[] = 'tag1'
            tags[] = 'tag2'
            excludeTags[] = 'tag3'
            excludeTags[] = 'tag4'

            [service2.0]
            _service_name_ = 'service2'
            ; service configuration
            class = 'ServiceClass2'
            isActive = true
            tags[] = 'tag5'
            tags[] = 'tag6'
            excludeTags[] = 'tag7'
            excludeTags[] = 'tag8'
            INI;


        \file_put_contents($file, $config);

        $serviceConfig              = new ServiceConfig($this->appDir);

        $result                     = $serviceConfig->findServiceConfig('service1');

        $this->assertEquals('ServiceClass1', $result['class']);
        $this->assertTrue($result['isActive'], 'Service must be active');
        $this->assertEquals(['tag1', 'tag2'], $result['tags']);
        $this->assertEquals(['tag3', 'tag4'], $result['excludeTags']);

        $result                     = $serviceConfig->findServiceConfig('service2');

        $this->assertEquals('ServiceClass2', $result['class']);
        $this->assertTrue($result['isActive'], 'Service must be active');
        $this->assertEquals(['tag5', 'tag6'], $result['tags']);
        $this->assertEquals(['tag7', 'tag8'], $result['excludeTags']);
    }

    public function testServiceCollection(): void
    {
        $file                       = $this->appDir . '/services.ini';

        $config = <<<INI
            [service1.0]
            _service_name_ = 'service1'
            package = 'package1'
            ; service configuration
            class = 'ServiceClass1'
            isActive = true
            tags[] = 'tag1'
            tags[] = 'tag2'
            excludeTags[] = 'tag3'
            excludeTags[] = 'tag4'

            [service1.1]
            _service_name_ = 'service1'
            package = 'package1'
            ; service configuration
            class = 'ServiceClass2'
            isActive = false
            tags[] = 'tag5'
            tags[] = 'tag6'
            excludeTags[] = 'tag7'
            excludeTags[] = 'tag8'

            [service1.2]
            _service_name_ = 'service1'
            package = 'package2'
            ; service configuration
            class = 'ServiceClass3'
            isActive = true
            tags[] = 'tag5'
            tags[] = 'tag6'
            excludeTags[] = 'tag7'
            excludeTags[] = 'tag8'
            INI;


        \file_put_contents($file, $config);

        $serviceConfig              = new ServiceConfig($this->appDir);

        $services                   = $serviceConfig->getServiceCollection(packageName: 'package1');

        $this->assertIsArray($services, 'Services must be an array');
        $this->assertArrayHasKey('service1', $services, 'Services must have service1 key');
        $this->assertCount(2, $services['service1'], 'Services1 must have 2 elements');

        $this->assertEquals('ServiceClass1', $services['service1']['0']['class']);
        $this->assertTrue($services['service1']['0']['isActive']);
        $this->assertEquals(['tag1', 'tag2'], $services['service1']['0']['tags']);
        $this->assertEquals(['tag3', 'tag4'], $services['service1']['0']['excludeTags']);

        $this->assertEquals('ServiceClass2', $services['service1']['1']['class']);
        $this->assertFalse($services['service1']['1']['isActive']);
        $this->assertEquals(['tag5', 'tag6'], $services['service1']['1']['tags']);
        $this->assertEquals(['tag7', 'tag8'], $services['service1']['1']['excludeTags']);

        $services                   = $serviceConfig->getServiceCollection(packageName: 'package2');

        $this->assertIsArray($services, 'Services must be an array');
        $this->assertCount(1, $services, 'Services must have 1 element');

        $this->assertEquals('ServiceClass3', $services['service1']['2']['class']);
        $this->assertTrue($services['service1']['2']['isActive']);
        $this->assertEquals(['tag5', 'tag6'], $services['service1']['2']['tags']);
    }

}
