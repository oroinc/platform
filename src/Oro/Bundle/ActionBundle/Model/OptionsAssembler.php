<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;

class OptionsAssembler
{
    /**
     * @var ConfigurationPassInterface[]
     */
    protected $configurationPasses = array();

    /**
     * @param array $options
     * @return array
     */
    public function assemble(array $options)
    {
        return $this->passConfiguration($options);
    }

    /**
     * @param ConfigurationPassInterface $configurationPass
     */
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
