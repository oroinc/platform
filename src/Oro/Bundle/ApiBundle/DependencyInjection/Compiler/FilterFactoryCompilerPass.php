<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures factories responsible to create instances of filters that can be used in Data API.
 */
class FilterFactoryCompilerPass implements CompilerPassInterface
{
    private const FILTER_FACTORY_SERVICE_ID         = 'oro_api.filter_factory';
    private const FILTER_FACTORY_TAG                = 'oro.api.filter_factory';
    private const DEFAULT_FILTER_FACTORY_SERVICE_ID = 'oro_api.filter_factory.default';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $filterFactoryServiceDef = DependencyInjectionUtil::findDefinition(
            $container,
            self::DEFAULT_FILTER_FACTORY_SERVICE_ID
        );
        if (null !== $filterFactoryServiceDef) {
            $config = DependencyInjectionUtil::getConfig($container);
            foreach ($config['filters'] as $filterType => $parameters) {
                if (isset($parameters['factory'])) {
                    $factory = $parameters['factory'];
                    unset($parameters['factory']);
                    $filterFactoryServiceDef->addMethodCall(
                        'addFilterFactory',
                        [$filterType, new Reference(substr($factory[0], 1)), $factory[1], $parameters]
                    );
                } else {
                    $filterClassName = $parameters['class'];
                    unset($parameters['class']);
                    $filterFactoryServiceDef->addMethodCall(
                        'addFilter',
                        [$filterType, $filterClassName, $parameters]
                    );
                }
            }
        }
        DependencyInjectionUtil::registerTaggedServices(
            $container,
            self::FILTER_FACTORY_SERVICE_ID,
            self::FILTER_FACTORY_TAG,
            'addFilterFactory'
        );
    }
}
