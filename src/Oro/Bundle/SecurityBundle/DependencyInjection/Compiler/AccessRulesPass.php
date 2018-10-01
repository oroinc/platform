<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass that collects access rules.
 */
class AccessRulesPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    private const TAG_NAME = 'oro_security.access_rule';
    private const SERVICE_ID = 'oro_security.access_rule.chain_access_rule';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::SERVICE_ID)) {
            return;
        }

        $taggedServices = $this->findAndSortTaggedServices(self::TAG_NAME, $container);

        if ($taggedServices) {
            $collectionServiceDefinition = $container->getDefinition(self::SERVICE_ID);
            foreach ($taggedServices as $service) {
                $collectionServiceDefinition->addMethodCall('addRule', [$service]);
            }
        }
    }
}
