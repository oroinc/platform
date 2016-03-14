<?php

namespace Oro\Bundle\ActionBundle\Model\Assembler;

use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;

interface ConfigurationPassesAwareInterface
{
    /**
     * @param ConfigurationPassInterface $configurationPass
     */
    public function addConfigurationPass(ConfigurationPassInterface $configurationPass);
}
