<?php

namespace Oro\Bundle\ActionBundle\Model\Assembler;

use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;

/**
 * Provides functionality for managing and applying configuration passes.
 *
 * This trait implements the {@see ConfigurationPassesAwareInterface}, allowing classes
 * to register and apply configuration passes to transform configuration data.
 */
trait ConfigurationPassesAwareTrait
{
    /**
     * @var array|ConfigurationPassInterface[]
     */
    private $configurationPasses = [];

    public function addConfigurationPass(ConfigurationPassInterface $configurationPass)
    {
        $this->configurationPasses[] = $configurationPass;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function passConfiguration(array $data)
    {
        foreach ($this->configurationPasses as $configurationPass) {
            $data = $configurationPass->passConfiguration($data);
        }

        return $data;
    }
}
