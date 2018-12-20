<?php

namespace Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheChain;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This compiler pass for testing that arguments and calls parameters
 * setted in another compiler passes are References instead of Definition
 */
class CheckReferenceCompilerPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    private $entityConfigProviders = null;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->getDefinitions() as $definition) {
            $arguments = $definition->getArguments();
            $calls = $definition->getMethodCalls();

            if ($arguments) {
                foreach ($arguments as $argument) {
                    $this->checkIsReference($container, $definition, '__construct', $argument);
                }
            }

            if ($calls) {
                foreach ($calls as $call) {
                    $this->checkCall($container, $definition, $call);
                }
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition $definition
     * @param array $call
     * @throws \Exception
     */
    private function checkCall(ContainerBuilder $container, Definition $definition, array $call)
    {
        if (count($call[1]) === 1) {
            $parameters = $call[1];
            $method = $call[0];
            foreach ($parameters as $parameter) {
                $this->checkIsReference($container, $definition, $method, $parameter);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition $definition
     * @param $method
     * @param $parameter
     * @throws \Exception
     */
    private function checkIsReference(ContainerBuilder $container, Definition $definition, $method, $parameter)
    {
        if ($parameter instanceof Definition) {
            if ($this->isEntityConfigProviderService($container, $parameter)) {
                return;
            }
            if ($definition->getClass() === MemoryCacheChain::class) {
                return;
            }

            throw new \Exception(sprintf(
                'Service %s has definition of service %s as parameter in method %s. Should be %s instead of %s',
                $definition->getClass(),
                $parameter->getClass(),
                $method,
                Reference::class,
                Definition::class
            ));
        }
    }

    /**
     * Check that definition has appropriate id in service container or is in allowed definitions
     * @param ContainerBuilder $container
     * @param Definition $definition
     * @return bool
     */
    private function isEntityConfigProviderService(ContainerBuilder $container, Definition $definition)
    {
        $definitionId = array_search($definition, $container->getDefinitions());

        return !$definitionId || in_array($definitionId, $this->getEntityConfigProviders($container));
    }

    /**
     * All oro_entity_config.provider. services should be pass because this definition created dynamically
     * @param ContainerBuilder $container
     * @return array
     */
    private function getEntityConfigProviders(ContainerBuilder $container)
    {
        if ($this->entityConfigProviders === null) {
            $serviceIds = array_keys($container->getDefinitions());
            $this->entityConfigProviders = array_filter(
                $serviceIds,
                function ($id) {
                    return strpos($id, 'oro_entity_config.provider.') === 0;
                }
            );
        }

        return $this->entityConfigProviders;
    }
}
