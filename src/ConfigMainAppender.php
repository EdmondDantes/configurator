<?php

declare(strict_types=1);

namespace IfCastle\Configurator;

use IfCastle\Application\Bootloader\BootManager\MainConfigAppenderInterface;
use IfCastle\DI\Exceptions\ConfigException;
use IfCastle\Exceptions\RuntimeException;
use IfCastle\OsUtilities\FileSystem\Exceptions\FileIsNotExistException;
use IfCastle\OsUtilities\Safe;

final class ConfigMainAppender extends ConfigIniMutable implements MainConfigAppenderInterface
{
    public function __construct(string $appDir)
    {
        parent::__construct($appDir . '/main.ini');
    }

    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     * @throws ConfigException
     */
    #[\Override]
    public function appendSectionIfNotExists(string $section, array $data): void
    {
        $this->load();

        $node                       = $this->referenceBy($section);

        if ($node !== null) {
            return;
        }

        $iniString                  = PHP_EOL.\implode(PHP_EOL, $this->build($this->data));

        Safe::execute(fn() => \file_put_contents($this->file, $iniString, \FILE_APPEND));
        $this->reset();
    }
}
