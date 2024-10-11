<?php
declare(strict_types=1);

namespace IfCastle\Configurator;

use PHPUnit\Framework\TestCase;

class ConfigIniMutableTest          extends TestCase
{
    public function testSave(): void
    {
        if(file_exists('./test.ini')) {
            unlink('./test.ini');
        }
        
        // create a new file
        file_put_contents('./test.ini', '');
        
        $config                     = new ConfigIniMutable('./test.ini');
        
        $config->set('foo', 'bar');
        $config->set('baz', 'qux');
        $config->save();
        
        $this->assertFileExists('./test.ini');
        $expected                   = <<<INI
foo = "bar"
baz = "qux"
INI;
        
        $this->assertEquals($expected, file_get_contents('test.ini'));
    }
}
