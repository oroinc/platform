<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;

/**
 * Removes temporary container parameter that is used to share ApiBundle configuration.
 * @see \Oro\Bundle\ApiBundle\DependencyInjection\OroApiExtension::load
 */
class RemoveConfigParameterCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $parameterBag = $container->getParameterBag();
        $parameterBag->set(DependencyInjectionUtil::API_BUNDLE_CONFIG_PARAMETER_NAME, null);
        if ($parameterBag instanceof ParameterBag) {
            $parameterBag->remove(DependencyInjectionUtil::API_BUNDLE_CONFIG_PARAMETER_NAME);
        }
    }
}
