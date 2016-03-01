<?php

namespace Oro\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class ExtendedContainerBuilder extends ContainerBuilder
{
    /**
     * Set extension config
     *
     * Usable for extensions which requires to have just one config.
     * http://api.symfony.com/2.3/Symfony/Component/Config/Definition/Builder/ArrayNodeDefinition.html
     * #method_disallowNewKeysInSubsequentConfigs
     */
    public function setExtensionConfig($name, array $config = [])
    {
        $classRef            = new \ReflectionClass('Symfony\Component\DependencyInjection\ContainerBuilder');
        $extensionConfigsRef = $classRef->getProperty('extensionConfigs');
        $extensionConfigsRef->setAccessible(true);

        $newConfig        = $extensionConfigsRef->getValue($this);
        $newConfig[$name] = $config;
        $extensionConfigsRef->setValue($this, $newConfig);

        $extensionConfigsRef->setAccessible(false);
    }

    /**
     * Changes a priority of a compiler pass
     *
     * @param string $passClassName       The class name of a compiler pass to be moved
     * @param string $targetPassClassName The class name of a target compiler pass
     * @param string $type                The type of compiler pass
     */
    public function moveCompilerPassBefore(
        $passClassName,
        $targetPassClassName,
        $type = PassConfig::TYPE_BEFORE_OPTIMIZATION
    ) {
        $passConfig = $this->getCompilerPassConfig();

        $propName = $type . 'Passes';
        $class    = new \ReflectionClass($passConfig);
        if (!$class->hasProperty($propName)) {
            throw new InvalidArgumentException(sprintf('Invalid compiler pass type "%s".', $type));
        }
        $prop = $class->getProperty($propName);
        $prop->setAccessible(true);
        $passes = $prop->getValue($passConfig);

        $resultPasses    = [];
        $srcPass         = null;
        $targetPassIndex = -1;
        foreach ($passes as $i => $pass) {
            switch (get_class($pass)) {
                case $passClassName:
                    $srcPass = $pass;
                    break;
                case $targetPassClassName:
                    if (null !== $srcPass) {
                        // the source pass is already located before the target pass
                        $resultPasses = null;
                        break 2;
                    }
                    // in case if several instances of the target pass exist
                    // the source pass should be located before the first instance of the target pass
                    if (-1 === $targetPassIndex) {
                        $targetPassIndex = count($resultPasses);
                        $resultPasses[]  = null;
                    }
                    $resultPasses[] = $pass;
                    break;
                default:
                    $resultPasses[] = $pass;
                    break;
            }
        }
        if (null !== $resultPasses) {
            if (null === $srcPass) {
                throw new InvalidArgumentException(sprintf('Unknown compiler pass "%s".', $passClassName));
            }
            if (-1 === $targetPassIndex) {
                throw new InvalidArgumentException(sprintf('Unknown compiler pass "%s".', $targetPassClassName));
            }
            $resultPasses[$targetPassIndex] = $srcPass;
            $prop->setValue($passConfig, $resultPasses);
        }
    }
}
