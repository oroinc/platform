<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects all email template variable providers and add them to the chain provider.
 */
class EmailTemplateVariablesPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

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
            $scope = $this->getRequiredAttribute($attributes, self::SCOPE_ATTR, $serviceId, self::PROVIDER_TAG);
            $priority = $this->getPriorityAttribute($attributes);
            if (self::SCOPE_SYSTEM === $scope) {
                $systemProviders[$priority][] = $serviceId;
            } elseif (self::SCOPE_ENTITY === $scope) {
                $entityProviders[$priority][] = $serviceId;
            } else {
                throw new InvalidArgumentException(sprintf(
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
}
