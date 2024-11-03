<?php

declare(strict_types=1);

namespace IfCastle\Configurator;

class ServiceConfigWriterTest extends ServiceConfigTestCase
{
    public function testAddServiceConfig(): void
    {
        $file                       = $this->appDir . '/services.ini';
        $config                     = new ServiceConfigWriter($this->appDir);

        $config->addServiceConfig('package1', 'service1', ['value1' => 'value1'], true, ['tag1', 'tag2'], ['exclude1', 'exclude2']);
        $config->addServiceConfig('package1', 'service2', ['value2' => 'value2'], false, ['tag3', 'tag4'], ['exclude3', 'exclude4']);
        $config->saveRepository();

        $data                       = \parse_ini_file($file, true, INI_SCANNER_TYPED);
        $expected                   = [
            'service1.0'            =>
                [
                    '_service_name_' => 'service1',
                    'package'       => 'package1',
                    'value1'        => 'value1',
                    'isActive'      => true,
                    'tags'          =>
                        [
                            0       => 'tag1',
                            1       => 'tag2',
                        ],
                    'excludeTags'   =>
                        [
                            0       => 'exclude1',
                            1       => 'exclude2',
                        ],
                ],
            'service2.0'            =>
                [
                    '_service_name_' => 'service2',
                    'package'       => 'package1',
                    'value2'        => 'value2',
                    'isActive'      => false,
                    'tags'          =>
                        [
                            0       => 'tag3',
                            1       => 'tag4',
                        ],
                    'excludeTags'   =>
                        [
                            0       => 'exclude3',
                            1       => 'exclude4',
                        ],
                ],
        ];

        $this->assertNotFalse($data, 'File not found');
        $this->assertEquals($expected, $data, 'Data not equals');
    }

    public function testRemoveServiceConfig(): void
    {
        $file                       = $this->appDir . '/services.ini';
        $config                     = new ServiceConfigWriter($this->appDir);

        $config->addServiceConfig(
            'package1',
            'service1',
            ['value1' => 'value1'],
            true,
            ['tag1', 'tag2'],
            ['exclude1', 'exclude2']
        );

        $config->addServiceConfig(
            'package1',
            'service2',
            ['value2' => 'value2'],
            false,
            ['tag3', 'tag4'],
            ['exclude3', 'exclude4']
        );

        $config->removeServiceConfig('package1', 'service1');

        $config->saveRepository();

        $data                       = \parse_ini_file($file, true, INI_SCANNER_TYPED);
        $expected                   = [
            'service2.0'            =>
                [
                    '_service_name_' => 'service2',
                    'package'       => 'package1',
                    'value2'        => 'value2',
                    'isActive'      => false,
                    'tags'          =>
                        [
                            0       => 'tag3',
                            1       => 'tag4',
                        ],
                    'excludeTags'   =>
                        [
                            0       => 'exclude3',
                            1       => 'exclude4',
                        ],
                ],
        ];

        $this->assertNotFalse($data, 'File not found');
        $this->assertEquals($expected, $data, 'Data not equals');
    }

    public function testUpdateServiceConfig(): void
    {
        $file                       = $this->appDir . '/services.ini';
        $config                     = new ServiceConfigWriter($this->appDir);

        $config->addServiceConfig(
            'package1',
            'service1',
            ['value1' => 'value1'],
            true,
            ['tag1', 'tag2'],
            ['exclude1', 'exclude2']
        );

        $config->updateServiceConfig(
            'package1',
            'service1',
            ['value1' => 'value1', 'value2' => 'value2'],
            ['tag3', 'tag4'],
            ['exclude3', 'exclude4']
        );

        $config->saveRepository();

        $data                       = \parse_ini_file($file, true, INI_SCANNER_TYPED);
        $expected                   = [
            'service1.0'            =>
                [
                    '_service_name_' => 'service1',
                    'package'       => 'package1',
                    'value1'        => 'value1',
                    'value2'        => 'value2',
                    'isActive'      => true,
                    'tags'          =>
                        [
                            0       => 'tag3',
                            1       => 'tag4',
                        ],
                    'excludeTags'   =>
                        [
                            0       => 'exclude3',
                            1       => 'exclude4',
                        ],
                ],
        ];

        $this->assertNotFalse($data, 'File not found');
        $this->assertEquals($expected, $data, 'Data not equals');
    }

    public function testActivateService(): void
    {
        $file                       = $this->appDir . '/services.ini';
        $config                     = new ServiceConfigWriter($this->appDir);

        $config->addServiceConfig(
            'package1',
            'service1',
            ['value1' => 'value1'],
            false,
            ['tag1', 'tag2'],
            ['exclude1', 'exclude2']
        );

        $config->activateService('package1', 'service1', '0');

        $config->saveRepository();

        $data                       = \parse_ini_file($file, true, INI_SCANNER_TYPED);
        $expected                   = [
            'service1.0'            =>
                [
                    '_service_name_' => 'service1',
                    'package'       => 'package1',
                    'value1'        => 'value1',
                    'isActive'      => true,
                    'tags'          =>
                        [
                            0       => 'tag1',
                            1       => 'tag2',
                        ],
                    'excludeTags'   =>
                        [
                            0       => 'exclude1',
                            1       => 'exclude2',
                        ],
                ],
        ];

        $this->assertNotFalse($data, 'File not found');
        $this->assertEquals($expected, $data, 'Data not equals');
    }

    public function testDeactivateService(): void
    {
        $file                       = $this->appDir . '/services.ini';
        $config                     = new ServiceConfigWriter($this->appDir);

        $config->addServiceConfig(
            'package1',
            'service1',
            ['value1' => 'value1'],
            true,
            ['tag1', 'tag2'],
            ['exclude1', 'exclude2']
        );

        $config->deactivateService('package1', 'service1', '0');

        $config->saveRepository();

        $data                       = \parse_ini_file($file, true, INI_SCANNER_TYPED);
        $expected                   = [
            'service1.0'            =>
                [
                    '_service_name_' => 'service1',
                    'package'       => 'package1',
                    'value1'        => 'value1',
                    'isActive'      => false,
                    'tags'          =>
                        [
                            0       => 'tag1',
                            1       => 'tag2',
                        ],
                    'excludeTags'   =>
                        [
                            0       => 'exclude1',
                            1       => 'exclude2',
                        ],
                ],
        ];

        $this->assertNotFalse($data, 'File not found');
        $this->assertEquals($expected, $data, 'Data not equals');
    }

    public function testChangeServiceTags(): void
    {
        $file                       = $this->appDir . '/services.ini';
        $config                     = new ServiceConfigWriter($this->appDir);

        $config->addServiceConfig(
            'package1',
            'service1',
            ['value1' => 'value1'],
            true,
            ['tag1', 'tag2'],
            ['exclude1', 'exclude2']
        );

        $config->changeServiceTags('package1', 'service1', '0', ['tag3', 'tag4'], ['exclude3', 'exclude4']);

        $config->saveRepository();

        $data                       = \parse_ini_file($file, true, INI_SCANNER_TYPED);
        $expected                   = [
            'service1.0'            =>
                [
                    '_service_name_' => 'service1',
                    'package'       => 'package1',
                    'value1'        => 'value1',
                    'isActive'      => true,
                    'tags'          =>
                        [
                            0       => 'tag3',
                            1       => 'tag4',
                        ],
                    'excludeTags'   =>
                        [
                            0       => 'exclude3',
                            1       => 'exclude4',
                        ],
                ],
        ];

        $this->assertNotFalse($data, 'File not found');
        $this->assertEquals($expected, $data, 'Data not equals');
    }
}
