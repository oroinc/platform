<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\DependencyInjection\TypedReference;

/**
 * Finds all service locators used by all services that are tagged with a specific tag,
 * merges them in one service locator with the given name
 * and replaces all found service locators with the new one.
 * If the target service locator already have services they are merged with services from found service locators.
 */
class MergeServiceLocatorsCompilerPass implements CompilerPassInterface
{
    public function __construct(
        private readonly string $tagName,
        private readonly string $serviceLocatorServiceId
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $serviceLocatorDefinition = $this->getServiceLocatorDefinition($container);
        $services = $serviceLocatorDefinition->getArgument(0);

        $locators = [];
        $servicesIds = [];
        $taggedServices = $container->findTaggedServiceIds($this->tagName);
        foreach ($taggedServices as $id => $tags) {
            $foundLocators = $this->findServiceLocators($container, $id);
            if ($foundLocators) {
                foreach ($foundLocators as $locator) {
                    $locators[$id][] = $locator;
                    foreach ($locator[0]->getArgument(0) as $serviceId => $serviceRef) {
                        if (isset($services[$serviceId])) {
                            $this->assertServicesEqual(
                                $serviceId,
                                $id,
                                $serviceRef,
                                $servicesIds[$serviceId],
                                $services[$serviceId]
                            );
                        } else {
                            $services[$serviceId] = $serviceRef;
                            $servicesIds[$serviceId] = $id;
                        }
                    }
                }
            }
        }
        $serviceLocatorDefinition->setArgument(0, $services);

        foreach ($locators as $id => $foundLocators) {
            foreach ($foundLocators as $locator) {
                $definition = $container->getDefinition($id);
                if ('argument' === $locator[1]) {
                    $definition->setArgument($locator[2], new Reference($this->serviceLocatorServiceId));
                } elseif ('methodCall' === $locator[1]) {
                    $methodCalls = $definition->getMethodCalls();
                    [$methodName, $methodArguments] = $methodCalls[$locator[2]];
                    $methodArguments[key($methodArguments)] = new Reference($this->serviceLocatorServiceId);
                    $methodCalls[$locator[2]] = [$methodName, $methodArguments];
                    $definition->setMethodCalls($methodCalls);
                }
            }
        }
    }

    private function getServiceLocatorDefinition(ContainerBuilder $container): Definition
    {
        if ($container->hasDefinition($this->serviceLocatorServiceId)) {
            return $container->getDefinition($this->serviceLocatorServiceId);
        }

        return $container->register($this->serviceLocatorServiceId, ServiceLocator::class)
            ->setPublic(false)
            ->addArgument([])
            ->addTag('container.service_locator');
    }

    private function findServiceLocators(ContainerBuilder $container, string $id): ?array
    {
        $locators = [];
        $definition = $container->getDefinition($id);
        foreach ($definition->getArguments() as $key => $argument) {
            $locatorDef = $this->getArgumentServiceLocatorDefinition($container, $argument);
            if (null !== $locatorDef) {
                $locators[] = [$locatorDef, 'argument', $key];
            }
        }

        foreach ($definition->getMethodCalls() as $key => [$methodName, $methodArguments]) {
            if ($methodArguments) {
                $locatorDef = $this->getArgumentServiceLocatorDefinition($container, reset($methodArguments));
                if (null !== $locatorDef) {
                    $locators[] = [$locatorDef, 'methodCall', $key];
                }
            }
        }

        if (!$locators) {
            return null;
        }

        return $locators;
    }

    private function getArgumentServiceLocatorDefinition(ContainerBuilder $container, mixed $argument): ?Definition
    {
        if (!$argument instanceof Reference) {
            return null;
        }

        $referenceId = (string)$argument;
        if (!str_starts_with($referenceId, '.') || !$container->hasDefinition($referenceId)) {
            return null;
        }

        $referenceDefinition = $container->getDefinition($referenceId);
        if ($referenceDefinition->getClass() !== ServiceLocator::class) {
            return null;
        }

        $factory = $referenceDefinition->getFactory();
        if (null === $factory) {
            return $referenceDefinition;
        }

        if (!\is_array($factory)) {
            return null;
        }

        return $this->getArgumentServiceLocatorDefinition($container, $factory[0]);
    }

    private function assertServicesEqual(
        string $serviceAlias,
        string $sourceServiceId,
        mixed $serviceRef,
        string $existingSourceServiceId,
        mixed $existingServiceRef
    ): void {
        $serviceId = $this->getTargetServiceId($serviceRef, $serviceAlias, $sourceServiceId);
        $existingServiceId = $this->getTargetServiceId($existingServiceRef, $serviceAlias, $existingSourceServiceId);
        if ($serviceId !== $existingServiceId) {
            throw new InvalidArgumentException(\sprintf(
                'Detected ambiguous service alias in the "%s" service locator.'
                . ' The alias "%s" has two services with different IDs,'
                . ' "%s" (defined in "%s" service) and "%s" (defined in "%s" service).',
                $this->serviceLocatorServiceId,
                $serviceAlias,
                $existingServiceId,
                $existingSourceServiceId,
                $serviceId,
                $sourceServiceId
            ));
        }
        $serviceType = $this->getTargetServiceType($serviceRef, $serviceAlias, $sourceServiceId);
        $existingServiceType = $this->getTargetServiceType(
            $existingServiceRef,
            $serviceAlias,
            $existingSourceServiceId
        );
        if ($serviceType !== $existingServiceType) {
            throw new InvalidArgumentException(\sprintf(
                'Detected ambiguous service alias in the "%s" service locator.'
                . ' The alias "%s" has two services with different types,'
                . ' "%s" (defined in "%s" service) and "%s" (defined in "%s" service).',
                $this->serviceLocatorServiceId,
                $serviceAlias,
                $existingServiceType ?? 'null',
                $existingSourceServiceId,
                $serviceType ?? 'null',
                $sourceServiceId
            ));
        }
    }

    private function getTargetServiceId(mixed $serviceRef, string $serviceAlias, string $sourceServiceId): string
    {
        if ($serviceRef instanceof ServiceClosureArgument) {
            $serviceRefValue = $serviceRef->getValues()[0];
            if ($serviceRefValue instanceof Reference) {
                return (string)$serviceRefValue;
            }
        }
        throw new \LogicException(\sprintf(
            'Cannot retrieve the target service ID for the service alias "%s" (defined in "%s" service).',
            $serviceAlias,
            $sourceServiceId
        ));
    }

    private function getTargetServiceType(mixed $serviceRef, string $serviceAlias, string $sourceServiceId): ?string
    {
        if ($serviceRef instanceof ServiceClosureArgument) {
            $serviceRefValue = $serviceRef->getValues()[0];
            if ($serviceRefValue instanceof Reference) {
                return $serviceRefValue instanceof TypedReference
                    ? $serviceRefValue->getType()
                    : null;
            }
        }
        throw new \LogicException(\sprintf(
            'Cannot retrieve the target service type for the service alias "%s" (defined in "%s" service).',
            $serviceAlias,
            $sourceServiceId
        ));
    }
}
