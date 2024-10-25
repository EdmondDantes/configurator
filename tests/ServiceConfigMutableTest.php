<?php
declare(strict_types=1);

namespace IfCastle\Configurator;

class ServiceConfigMutableTest extends ServiceConfigTestCase
{
    public function testSet(): void
    {
        $file                       = $this->appDir.'/services.ini';
        $config                     = new ServiceConfigMutable($this->appDir);

        $config->addServiceConfig('service1', ['value1' => 'value1'], true, ['tag1', 'tag2'], ['exclude1', 'exclude2']);
        $config->addServiceConfig('service2', ['value2' => 'value2'], false, ['tag3', 'tag4'], ['exclude3', 'exclude4']);
        $config->saveRepository();
        
        $data                       = parse_ini_file($file, true, INI_SCANNER_TYPED);
        $expected                   = [
            'service1'              =>
                [
                    'value1'        => 'value1',
                    'isActive'      => true,
                    'tags'          =>
                        [
                            0 => 'tag1',
                            1 => 'tag2',
                        ],
                    'excludeTags' =>
                        [
                            0 => 'exclude1',
                            1 => 'exclude2',
                        ],
                ],
            'service2' =>
                [
                    'value2'   => 'value2',
                    'isActive' => false,
                    'tags'     =>
                        [
                            0 => 'tag3',
                            1 => 'tag4',
                        ],
                    'excludeTags' =>
                        [
                            0 => 'exclude3',
                            1 => 'exclude4',
                        ],
                ],
        ];
        
        $this->assertNotFalse($data, 'File not found');
        $this->assertEquals($expected, $data, 'Data not equals');
    }
}
