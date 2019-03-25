<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects all email template variable processors and add them to the registry.
 */
class VariableProcessorPass implements CompilerPassInterface
{
    private const REGISTRY_SERVICE = 'oro_email.emailtemplate.variable_processor';
    private const PROCESSOR_TAG    = 'oro_email.emailtemplate.variable_processor';

    private const ALIAS_ATTR = 'alias';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processors = [];
        $taggedServices = $container->findTaggedServiceIds(self::PROCESSOR_TAG);
        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                if (empty($attributes[self::ALIAS_ATTR])) {
                    throw new \InvalidArgumentException(sprintf(
                        'The tag attribute "%s" is required for service "%s".',
                        self::ALIAS_ATTR,
                        $serviceId
                    ));
                }

                $processors[$attributes[self::ALIAS_ATTR]] = new Reference($serviceId);
            }
        }

        $container->getDefinition(self::REGISTRY_SERVICE)
            ->replaceArgument(0, ServiceLocatorTagPass::register($container, $processors));
    }
}
