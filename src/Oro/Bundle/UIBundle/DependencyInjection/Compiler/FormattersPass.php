<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects all formatters and add them to the formatter manager.
 */
class FormattersPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    private const MANAGER_SERVICE = 'oro_ui.formatter';
    private const FORMATTER_TAG   = 'oro_formatter';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $formatters = [];
        $typesMap = [];
        $taggedServices = $container->findTaggedServiceIds(self::FORMATTER_TAG);
        foreach ($taggedServices as $id => $tags) {
            $container->getDefinition($id)->setPublic(false);
            foreach ($tags as $attributes) {
                $formatter = $this->getRequiredAttribute($attributes, 'formatter', $id, self::FORMATTER_TAG);
                $formatters[$formatter] = new Reference($id);
                $dataType = $attributes['data_type'] ?? null;
                if ($dataType) {
                    $typesMap[$dataType] = $formatter;
                }
            }
        }

        $container->getDefinition(self::MANAGER_SERVICE)
            ->replaceArgument(0, ServiceLocatorTagPass::register($container, $formatters))
            ->replaceArgument(1, $typesMap);
    }
}
