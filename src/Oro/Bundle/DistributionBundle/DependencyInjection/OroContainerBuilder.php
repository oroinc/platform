<?php

namespace Oro\Bundle\DistributionBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroContainerBuilder extends ContainerBuilder
{
    /**
     * Set extension config
     * 
     * Usable for extensions which requires to have just one config.
     * http://api.symfony.com/2.3/Symfony/Component/Config/Definition/Builder/ArrayNodeDefinition.html#method_disallowNewKeysInSubsequentConfigs
     */
    public function setExtensionConfig($name, array $config = [])
    {
        $classRef = new \ReflectionClass('Symfony\Component\DependencyInjection\ContainerBuilder');
        $extensionConfigsRef = $classRef->getProperty('extensionConfigs');
        $extensionConfigsRef->setAccessible(true);

        $newConfig = $extensionConfigsRef->getValue($this);
        $newConfig[$name] = $config;
        $extensionConfigsRef->setValue($this, $newConfig);

        $extensionConfigsRef->setAccessible(false);
    }
}
