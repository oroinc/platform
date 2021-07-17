<?php

namespace Oro\Bundle\ActionBundle\Model\Assembler;

use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;

interface ConfigurationPassesAwareInterface
{
    public function addConfigurationPass(ConfigurationPassInterface $configurationPass);
}
