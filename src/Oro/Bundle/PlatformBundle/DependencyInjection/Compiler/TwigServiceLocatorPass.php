<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Contracts\Service\Attribute\SubscribedService;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\ExtensionInterface;

/**
 * Registers all services inside service locator which are required by twig extensions.
 */
class TwigServiceLocatorPass implements CompilerPassInterface
{
    private const string TWIG_SERVICE_LOCATOR_SERVICE_ID = 'oro_platform.twig.service_locator';

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::TWIG_SERVICE_LOCATOR_SERVICE_ID)) {
            return;
        }

        $ids = [];
        $definitions = $container->getDefinitions();
        foreach ($definitions as $serviceId => $definition) {
            $this->getSubscribedServices($container, $definition, $serviceId, $ids);
        }

        $services = [];
        $serviceIds = [];
        foreach ($ids as $serviceId => $normalizedSubscribedServices) {
            $container->getDefinition($serviceId)->clearTag('container.service_subscriber');
            foreach ($normalizedSubscribedServices as $alias => $id) {
                if (isset($services[$alias])) {
                    $existingId = (string)$services[$alias];
                    if ($id !== $existingId) {
                        throw new InvalidArgumentException(\sprintf(
                            'Detected ambiguous service alias in the "%s" service locator.'
                            . ' The alias "%s" has two service with different IDs,'
                            . ' "%s" (defined in "%s" service) and "%s" (defined in "%s" service).',
                            self::TWIG_SERVICE_LOCATOR_SERVICE_ID,
                            $alias,
                            $existingId,
                            $serviceIds[$alias],
                            $id,
                            $serviceId
                        ));
                    }
                } elseif ($this->isNonSharedService($container, $id)) {
                    throw new InvalidArgumentException(\sprintf(
                        'Non-shared services in the "%s" service locator is not supported. Service: %s.',
                        self::TWIG_SERVICE_LOCATOR_SERVICE_ID,
                        $id
                    ));
                } else {
                    $services[$alias] = new Reference($id, ContainerInterface::IGNORE_ON_INVALID_REFERENCE);
                    $serviceIds[$alias] = $serviceId;
                }
            }
        }
        if (!$services) {
            return;
        }

        $container->getDefinition(self::TWIG_SERVICE_LOCATOR_SERVICE_ID)
            ->replaceArgument(0, $services);
    }

    private function getSubscribedServices(
        ContainerBuilder $container,
        Definition $definition,
        string $serviceId,
        array &$ids
    ): void {
        $class = $definition->getClass();
        try {
            if (!is_a($class, ExtensionInterface::class, true)) {
                return;
            }
        } catch (\Error) {
            // Catch class loading errors and do nothing as there are services in vendors for non-existent classes.
            return;
        }

        if (is_a($class, ServiceSubscriberInterface::class, true) && !isset($ids[$serviceId])) {
            $ids[$serviceId] = $this->getServiceIds(
                $class::getSubscribedServices(),
                $this->getServiceSubscriberTags($definition, $container),
                $serviceId
            );
        }

        $decorated = $definition->getDecoratedService();
        if (!$decorated) {
            return;
        }

        $decoratedServiceId = $decorated[0];
        $this->getSubscribedServices(
            $container,
            $container->getDefinition($decoratedServiceId),
            $decoratedServiceId,
            $ids
        );
        if (isset($ids[$decoratedServiceId])) {
            $ids[$serviceId] = isset($ids[$serviceId])
                ? array_merge($ids[$decoratedServiceId], $ids[$serviceId])
                : $ids[$decoratedServiceId];
        }
    }

    private function getServiceIds(
        array $subscribedServices,
        array $serviceSubscriberTags,
        string $serviceId
    ): array {
        $result = [];
        $normalizedSubscribedServices = $this->normalizeSubscribedServices($subscribedServices);
        foreach ($serviceSubscriberTags as $serviceSubscriberTag) {
            $id = $serviceSubscriberTag['id'] ?? null;
            if (!$id) {
                continue;
            }
            $alias = $serviceSubscriberTag['key'] ?? $id;
            if (isset($normalizedSubscribedServices[$alias])) {
                unset($normalizedSubscribedServices[$alias]);
                $result[$alias] = $id;
            } elseif ($alias !== $id && isset($normalizedSubscribedServices[$id])) {
                unset($normalizedSubscribedServices[$id]);
                $result[$alias] = $id;
            } else {
                $exceptionMessage = \sprintf(
                    'Invalid "container.service_subscriber" tag declaration for "%s" service.',
                    $serviceId
                );
                $exceptionMessage .= $alias === $id
                    ? \sprintf(' The "%s" service does not exist', $alias)
                    : \sprintf(' Neither the "%s" service nor the "%s" service exist', $id, $alias);
                $exceptionMessage .= ' in the list of services returned "getSubscribedServices()" method.';
                throw new InvalidArgumentException($exceptionMessage);
            }
        }
        foreach ($normalizedSubscribedServices as $alias => $nullable) {
            $result[$alias] = $alias;
        }

        return $result;
    }

    private function normalizeSubscribedServices(array $subscribedServices): array
    {
        $result = [];
        foreach ($subscribedServices as $alias => $type) {
            $nullable = false;
            if (\is_int($alias)) {
                if ($type instanceof SubscribedService) {
                    $nullable = $type->nullable;
                    $alias = $type->key ?? $type->type;
                } else {
                    $alias = $type;
                    if (str_starts_with($alias, '?')) {
                        $nullable = true;
                        $alias = substr($alias, 1);
                    }
                    if (str_ends_with($alias, '[]')) {
                        $alias = substr($alias, 0, -2);
                    }
                }
            }
            /** @var string $alias */
            $result[$alias] = $nullable;
        }

        return $result;
    }

    private function getServiceSubscriberTags(Definition $definition, ContainerBuilder $container): array
    {
        $tags = $definition->getTag('container.service_subscriber');
        $decorated = $definition->getDecoratedService();
        if ($decorated) {
            $decoratedServiceTags = $this->getServiceSubscriberTags(
                $container->getDefinition($decorated[0]),
                $container
            );
            if ($decoratedServiceTags) {
                $tags = $tags
                    ? array_merge($tags, $decoratedServiceTags)
                    : $decoratedServiceTags;
            }
        }

        return $tags;
    }

    private function isNonSharedService(ContainerBuilder $container, string $serviceId): bool
    {
        return
            ($container->hasDefinition($serviceId) || $container->hasAlias($serviceId))
            && !$container->findDefinition($serviceId)->isShared();
    }
}
