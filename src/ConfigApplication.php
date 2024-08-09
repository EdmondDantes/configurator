<?php
declare(strict_types=1);

namespace IfCastle\Configurator;

use IfCastle\Application\Bootloader\Builder\ZeroContextInterface;
use IfCastle\Application\Bootloader\Builder\ZeroContextRequiredInterface;

final class ConfigApplication       extends ConfigIni
                                    implements ZeroContextRequiredInterface
{
    public function __construct() { parent::__construct('!undefined!'); }
    
    
    #[\Override]
    public function setZeroContext(ZeroContextInterface $zeroContext): static
    {
        $this->file                 = $zeroContext->getApplicationDirectory() . '/main.ini';
        return $this;
    }
}