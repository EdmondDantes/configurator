<?php

declare(strict_types=1);

namespace IfCastle\Configurator;

use IfCastle\DI\ConfigInterface;
use IfCastle\Exceptions\RuntimeException;
use IfCastle\OsUtilities\FileSystem\Exceptions\FileIsNotExistException;
use IfCastle\OsUtilities\Safe;

class ConfigIni implements ConfigInterface
{
    private bool $isLoaded          = false;

    /**
     * @var array<string, mixed>
     */
    protected array $data           = [];

    public function __construct(protected string $file) {}

    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     */
    protected function load(): void
    {
        if ($this->isLoaded) {
            return;
        }

        $this->isLoaded             = true;

        if (false === \file_exists($this->file)) {
            throw new FileIsNotExistException($this->file);
        }

        $data                       = Safe::execute(fn() => \parse_ini_file(
            $this->file, true, \INI_SCANNER_TYPED),
        );

        if ($data === false) {
            throw new RuntimeException('Error occurred while parse ini file: ' . $this->file);
        }

        $this->data                 = [];

        // Convert all sections with dot notation to nest arrays
        foreach ($data as $section => $values) {

            $parts                  = \explode('.', (string) $section);

            if (\count($parts) === 1) {
                $this->data[$section] = $values;
                continue;
            }

            $pointer                = &$this->data;

            foreach ($parts as $part) {

                if (\array_key_exists($part, $pointer) === false) {
                    $pointer[$part] = [];
                }

                $pointer            = &$pointer[$part];
            }

            $pointer                = \array_merge($pointer, $values);
        }
    }

    public function find(string...$path): mixed
    {
        $this->load();

        $current                    = $this->data;

        while ($path !== []) {
            $key                    = \array_shift($path);

            if (!\is_array($current) || !\array_key_exists($key, $current)) {
                return null;
            }

            $current                = $current[$key];
        }

        return $current;
    }

    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     */
    #[\Override]
    public function findValue(string $key, mixed $default = null): mixed
    {
        $this->load();

        $path                       = \explode('.', $key);

        if (\count($path) === 1) {
            return $this->data[$key] ?? $default;
        }

        return $this->find(...$path) ?? $default;
    }

    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     */
    #[\Override]
    public function findSection(string $section): array
    {
        $this->load();

        $result                     = $this->findValue($section);

        if (!\is_array($result)) {
            return [];
        }

        return $result;
    }

    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     */
    #[\Override]
    public function requireValue(string $key): mixed
    {
        $this->load();

        $value                      = $this->findValue($key);

        if ($value === null) {
            throw new RuntimeException('Value not found for key: ' . $key);
        }

        return $value;
    }

    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     */
    #[\Override]
    public function requireSection(string $section): array
    {
        $this->load();

        $result                     = $this->findSection($section);

        if ($result === []) {
            throw new RuntimeException('Section not found or empty: ' . $section);
        }

        return $result;
    }

    protected function resetLoadedData(): void
    {
        $this->isLoaded             = false;
    }
}
