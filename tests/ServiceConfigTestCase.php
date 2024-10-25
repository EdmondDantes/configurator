<?php

declare(strict_types=1);

namespace IfCastle\Configurator;

use PHPUnit\Framework\TestCase;

class ServiceConfigTestCase extends TestCase
{
    protected string $appDir;

    #[\Override]
    protected function setUp(): void
    {
        $this->appDir               = __DIR__ . '/app';
        $this->cleanUpDir();
    }

    protected function cleanUpDir(): void
    {
        if (\is_dir($this->appDir)) {
            // remove all files
            $files = \glob($this->appDir . '/*');

            foreach ($files as $file) {
                if (\is_file($file)) {
                    \unlink($file);
                }
            }
        }

        if (!\is_dir($this->appDir)) {
            \mkdir($this->appDir);
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->cleanUpDir();

        if (\is_dir($this->appDir)) {
            \rmdir($this->appDir);
        }
    }
}
