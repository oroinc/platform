<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects all email template variable providers and add them to the chain provider.
 */
class EmailTemplateVariablesPass implements CompilerPassInterface
{
    private const CHAIN_PROVIDER_SERVICE = 'oro_email.emailtemplate.variable_provider';
    private const PROVIDER_TAG           = 'oro_email.emailtemplate.variable_provider';

    private const SCOPE_SYSTEM  = 'system';
    private const SCOPE_ENTITY  = 'entity';
    private const SCOPE_ATTR    = 'scope';
    private const PRIORITY_ATTR = 'priority';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $providers = [];
        $systemProviders = [];
        $entityProviders = [];
        $taggedServices = $container->findTaggedServiceIds(self::PROVIDER_TAG);
        foreach ($taggedServices as $serviceId => $tags) {
            $attributes = $tags[0];
            if (empty($attributes[self::SCOPE_ATTR])) {
                throw new \InvalidArgumentException(sprintf(
                    'The tag attribute "%s" is required for service "%s".',
                    self::SCOPE_ATTR,
                    $serviceId
                ));
            }

            $priority = $attributes[self::PRIORITY_ATTR] ?? 0;
            $scope = $attributes[self::SCOPE_ATTR];
            if (self::SCOPE_SYSTEM === $scope) {
                $systemProviders[$priority][] = $serviceId;
            } elseif (self::SCOPE_ENTITY === $scope) {
                $entityProviders[$priority][] = $serviceId;
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'The value "%s" is invalid for the tag attribute "%s" for service "%s", expected "%s" or "%s".',
                    $scope,
                    self::SCOPE_ATTR,
                    $serviceId,
                    self::SCOPE_SYSTEM,
                    self::SCOPE_ENTITY
                ));
            }

            $providers[$serviceId] = new Reference($serviceId);
        }

        $container->getDefinition(self::CHAIN_PROVIDER_SERVICE)
            ->replaceArgument(0, ServiceLocatorTagPass::register($container, $providers))
            ->replaceArgument(1, $this->sortByPriorityAndFlatten($systemProviders))
            ->replaceArgument(2, $this->sortByPriorityAndFlatten($entityProviders));
    }

    /**
     * @param array $items [priority => item, ...]
     *
     * @return array [item, ...]
     */
    private function sortByPriorityAndFlatten(array $items): array
    {
        if ($items) {
            krsort($items);
            $items = array_merge(...$items);
        }

        return $items;
    }
}
