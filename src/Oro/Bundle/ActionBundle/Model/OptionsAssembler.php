<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\ActionBundle\Model\Assembler\ConfigurationPassesAwareInterface;
use Oro\Bundle\ActionBundle\Model\Assembler\ConfigurationPassesAwareTrait;

/**
 * Assembles action options by applying configuration passes.
 *
 * This assembler processes action options through registered configuration passes,
 * allowing for transformation and validation of option data.
 */
class OptionsAssembler implements ConfigurationPassesAwareInterface
{
    use ConfigurationPassesAwareTrait;

    /**
     * @param array $options
     * @return array
     */
    public function assemble(array $options)
    {
        return $this->passConfiguration($options);
    }
}
