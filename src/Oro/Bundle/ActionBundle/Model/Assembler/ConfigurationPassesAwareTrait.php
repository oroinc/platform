<?php

namespace Oro\Bundle\ActionBundle\Model\Assembler;

use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;

trait ConfigurationPassesAwareTrait
{
    /**
     * @var array|ConfigurationPassInterface[]
     */
    private $configurationPasses = [];

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
