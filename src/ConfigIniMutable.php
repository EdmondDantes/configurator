<?php
declare(strict_types=1);

namespace IfCastle\Configurator;

use IfCastle\DI\ConfigMutableInterface;
use IfCastle\DI\Exceptions\ConfigException;
use IfCastle\Exceptions\RuntimeException;
use IfCastle\OsUtilities\FileSystem\Exceptions\FileIsNotExistException;
use IfCastle\OsUtilities\Safe;

class ConfigIniMutable              extends ConfigIni
                                    implements ConfigMutableInterface
{
    protected bool $wasModified = false;
    
    public function __construct(string $file, protected bool $isReadOnly = false)
    {
        parent::__construct($file);
    }
    
    public function save(): void
    {
        $this->throwReadOnly();
        
        $content                    = implode(PHP_EOL, $this->build($this->data));
        $result                     = Safe::execute(fn() => file_put_contents($this->file, $content));
        
        if(false === $result) {
            throw new RuntimeException('Error occurred while saving ini file: ' . $this->file);
        }
        
        $this->wasModified          = false;
    }
    
    protected function build(array $data, string $parentKey = ''): array
    {
        static $isNestedArray       = static function(array $data): bool {
            foreach ($data as $value) {
                if(is_array($value)) {
                    return true;
                }
            }
            
            return false;
        };
        
        $result                     = [];
        
        if($parentKey !== '') {
            $result[]               = '';
            $result[]               = ';' . str_repeat('-', 40);
            $result[]               = '[' . $parentKey . ']';
        }
        
        // 1. Check if any value is a nested array
        foreach ($data as $key => $value) {
            if(is_array($value) && $isNestedArray($value)) {
                $result             = array_merge($result, $this->build($value, $parentKey . $key . '.'));
            } else {
                $result[]           = $this->formatKeyValue($key, $value);
            }
        }
        
        return $result;
    }
    
    protected function formatKeyValue(string $key, mixed $value): string
    {
        return $key . ' = ' . $this->iniEncodeValue($value);
    }
    
    protected function iniEncodeValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_numeric($value)) {
            return $value;
        } else {
            return '"' . addcslashes($value, '"') . '"';
        }
    }
    
    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     * @throws ConfigException
     */
    #[\Override]
    public function set(string $node, mixed $value): static
    {
        $this->throwReadOnly();
        $this->load();
        
        $this->wasModified          = true;
        
        $this->data[$node]          = $value;
        
        return $this;
    }
    
    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws ConfigException
     * @throws \ErrorException
     */
    #[\Override]
    public function setSection(string $node, array $value): static
    {
        $this->throwReadOnly();
        $this->load();
        
        $this->wasModified          = true;
        
        $this->data[$node]          = $value;
        
        return $this;
    }
    
    #[\Override]
    public function merge(array $config): static
    {
        $this->throwReadOnly();
        $this->load();
        
        $this->wasModified         = true;
        
        $this->data                 = array_merge($this->data, $config);
        
        return $this;
    }
    
    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws \ErrorException
     * @throws ConfigException
     */
    #[\Override]
    public function mergeSection(string $node, array $config): static
    {
        $this->throwReadOnly($node);
        $this->load();
        
        $this->wasModified          = true;
        
        if(array_key_exists($node, $this->data) && is_array($this->data[$node])) {
            $this->data[$node]      = array_merge($this->data[$node], $config);
        } else {
            $this->data[$node]      = $config;
        }
        
        return $this;
    }
    
    /**
     * @throws RuntimeException
     * @throws FileIsNotExistException
     * @throws ConfigException
     * @throws \ErrorException
     */
    #[\Override]
    public function remove(string ...$path): static
    {
        $this->throwReadOnly();
        $this->load();
        
        $current                    = &$this->data;
        
        while ($path !== []) {
            $key                    = array_shift($path);
            
            if(!is_array($current) || !array_key_exists($key, $current)) {
                return $this;
            }
            
            $current                = &$current[$key];
        }
        
        $this->wasModified          = true;
        
        unset($current);
        
        return $this;
    }
    
    /**
     * @throws ConfigException
     */
    #[\Override]
    public function reset(): static
    {
        $this->throwReadOnly();
        $this->load();
        
        $this->wasModified          = true;
        
        $this->data                 = [];
        
        return $this;
    }
    
    #[\Override]
    public function asImmutable(): static
    {
        $this->isReadOnly           = true;
        return $this;
    }
    
    #[\Override]
    public function cloneAsMutable(): static
    {
        return new static($this->file, false);
    }
    
    /**
     * @throws ConfigException
     */
    protected function throwReadOnly(string $node = ''): void
    {
        if($this->isReadOnly) {
            throw new ConfigException('The config key ' . $node . ' is read only');
        }
    }
}
