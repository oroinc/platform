<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\ActionBundle\Model\Assembler\ConfigurationPassesAwareInterface;
use Oro\Bundle\ActionBundle\Model\Assembler\ConfigurationPassesAwareTrait;

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
