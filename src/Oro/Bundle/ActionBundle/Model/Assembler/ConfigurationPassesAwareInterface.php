<?php

namespace Oro\Bundle\ActionBundle\Model\Assembler;

use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;

/**
 * Defines the contract for objects that can accept configuration passes.
 */
interface ConfigurationPassesAwareInterface
{
    public function addConfigurationPass(ConfigurationPassInterface $configurationPass);
}
