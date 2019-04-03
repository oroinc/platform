<?php

namespace Oro\Bundle\FormBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects all form handlers and add them to the registry.
 */
class FormHandlerCompilerPass implements CompilerPassInterface
{
    private const REGISTRY_SERVICE = 'oro_form.registry.form_handler';
    private const HANDLER_TAG      = 'oro_form.form.handler';

    private const ALIAS_ATTR = 'alias';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $handlers = [];
        $taggedServices = $container->findTaggedServiceIds(self::HANDLER_TAG);
        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                if (empty($attributes[self::ALIAS_ATTR])) {
                    throw new \InvalidArgumentException(sprintf(
                        'The tag attribute "%s" is required for service "%s".',
                        self::ALIAS_ATTR,
                        $serviceId
                    ));
                }

                $handlers[$attributes[self::ALIAS_ATTR]] = new Reference($serviceId);
            }
        }

        $container->getDefinition(self::REGISTRY_SERVICE)
            ->replaceArgument(0, ServiceLocatorTagPass::register($container, $handlers));
    }
}
