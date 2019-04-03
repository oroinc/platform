<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects all formatters and add them to the formatter manager.
 */
class FormattersPass implements CompilerPassInterface
{
    private const MANAGER_SERVICE = 'oro_ui.formatter';
    private const FORMATTER_TAG   = 'oro_formatter';

    private const FORMATTER_ATTR = 'formatter';
    private const DATA_TYPE_ATTR = 'data_type';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $formatters = [];
        $typesMap = [];
        $taggedServices = $container->findTaggedServiceIds(self::FORMATTER_TAG);
        foreach ($taggedServices as $serviceId => $tags) {
            $container->getDefinition($serviceId)->setPublic(false);
            foreach ($tags as $attributes) {
                if (empty($attributes[self::FORMATTER_ATTR])) {
                    throw new \InvalidArgumentException(sprintf(
                        'The tag attribute "%s" is required for service "%s".',
                        self::FORMATTER_ATTR,
                        $serviceId
                    ));
                }

                $formatterName = $attributes[self::FORMATTER_ATTR];
                $formatters[$formatterName] = new Reference($serviceId);
                if (!empty($attributes[self::DATA_TYPE_ATTR])) {
                    $typesMap[$attributes[self::DATA_TYPE_ATTR]] = $formatterName;
                }
            }
        }

        $container->getDefinition(self::MANAGER_SERVICE)
            ->replaceArgument(0, ServiceLocatorTagPass::register($container, $formatters))
            ->replaceArgument(1, $typesMap);
    }
}
