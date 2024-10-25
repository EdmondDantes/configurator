<?php

declare(strict_types=1);

namespace IfCastle\Configurator;

class ConfigMain extends ConfigIni
{
    public function __construct(string $appDir)
    {
        parent::__construct($appDir . '/main.ini');
    }
}
