<?php

namespace Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class InlineEditColumnOptionsGuesserPass
 * @package Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass
 */
class InlineEditColumnOptionsGuesserPass implements CompilerPassInterface
{
    const INLINE_EDIT_COLUMN_OPTIONS_GUESSER_SERVICE = 'oro_datagrid.datagrid.inline_edit_column_options_guesser';
    const TAG = 'oro_datagrid.inline_edit_column_options_guesser';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::INLINE_EDIT_COLUMN_OPTIONS_GUESSER_SERVICE)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        if (empty($taggedServices)) {
            return;
        }

        $definition = $container->getDefinition(self::INLINE_EDIT_COLUMN_OPTIONS_GUESSER_SERVICE);

        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall(
                'addGuesser',
                [new Reference($id)]
            );
        }
    }
}
