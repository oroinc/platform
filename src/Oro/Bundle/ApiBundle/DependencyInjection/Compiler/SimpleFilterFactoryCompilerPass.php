<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures the default factory that creates instances of filters.
 * @see \Oro\Bundle\ApiBundle\Filter\SimpleFilterFactory
 */
class SimpleFilterFactoryCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $filters = [];
        $factories = [];
        $factoryServices = [];
        $config = DependencyInjectionUtil::getConfig($container);
        foreach ($config['filters'] as $filterType => $parameters) {
            if (isset($parameters['factory'])) {
                $factory = $parameters['factory'];
                unset($parameters['factory']);
                $factoryServiceId = substr($factory[0], 1);
                $factoryMethod = $factory[1];
                $this->validateFilterFactory($container, $factoryServiceId, $factoryMethod);
                $factoryServices[$factoryServiceId] = new Reference($factoryServiceId);
                $factories[$filterType] = [$factoryServiceId, $factoryMethod, $parameters];
            } else {
                $filterClassName = $parameters['class'];
                unset($parameters['class']);
                $filters[$filterType] = [$filterClassName, $parameters];
            }
        }
        $container->getDefinition('oro_api.filter_factory.default')
            ->replaceArgument(0, $filters)
            ->replaceArgument(1, $factories)
            ->replaceArgument(2, ServiceLocatorTagPass::register($container, $factoryServices));
    }

    private function validateFilterFactory(
        ContainerBuilder $container,
        string $factoryServiceId,
        string $factoryMethod
    ): void {
        $factoryClass = $container->getDefinition($factoryServiceId)->getClass();
        $factoryClass = $container->getParameterBag()->resolveValue($factoryClass);
        $refl = new \ReflectionClass($factoryClass);
        if (!$refl->hasMethod($factoryMethod)
            || !$refl->getMethod($factoryMethod)->isPublic()
            || 1 !== $refl->getMethod($factoryMethod)->getNumberOfParameters()
        ) {
            throw new \LogicException(sprintf(
                'The "%s($dataType)" public method must be declared in the "%s" class.',
                $factoryMethod,
                $factoryClass
            ));
        }
    }
}
