<?php

namespace Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler;

use Doctrine\Bundle\DoctrineBundle\Dbal\ManagerRegistryAwareConnectionProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Mime\MimeTypes;

/**
 * This compiler pass tests that services are injected into other services via a reference.
 */
class CheckReferenceCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $definition) {
            $arguments = $definition->getArguments();
            if ($arguments) {
                $this->checkArguments($container, $definition, '__construct', $arguments);
            }

            $calls = $definition->getMethodCalls();
            if ($calls) {
                foreach ($calls as $call) {
                    $this->checkCall($container, $definition, $call);
                }
            }
        }
    }

    private function checkArguments(
        ContainerBuilder $container,
        Definition $definition,
        string $method,
        array $arguments
    ): void {
        foreach ($arguments as $argument) {
            if ($argument instanceof Definition) {
                $this->assertDefinitionIsAllowedAsArgument($container, $definition, $method, $argument);
            }
        }
    }

    private function checkCall(ContainerBuilder $container, Definition $definition, array $call): void
    {
        if (count($call[1]) === 1) {
            [$method, $arguments] = $call;
            if ($arguments) {
                $this->checkArguments($container, $definition, $method, $arguments);
            }
        }
    }

    private function assertDefinitionIsAllowedAsArgument(
        ContainerBuilder $container,
        Definition $definition,
        string $method,
        Definition $argument
    ): void {
        if (in_array(
            $definition->getClass(),
            [
                MimeTypes::class,
                ManagerRegistryAwareConnectionProvider::class,
            ]
        )) {
            return;
        }
        if ($argument->getClass() === MimeTypes::class) {
            return;
        }

        $argumentServiceId = array_search($argument, $container->getDefinitions());
        if (!$argumentServiceId) {
            return;
        }

        throw new \RuntimeException(sprintf(
            'Service %s has definition of service %s as parameter in method %s. Should be %s instead of %s',
            $definition->getClass(),
            $argument->getClass(),
            $method,
            Reference::class,
            Definition::class
        ));
    }
}
