<?php

namespace Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collect datagrid column options guessers and set them to the main guesser.
 */
class GuessPass implements CompilerPassInterface
{
    const COLUMN_OPTIONS_GUESSER_ID       = 'oro_datagrid.datagrid.guesser';
    const COLUMN_OPTIONS_GUESSER_TAG_NAME = 'oro_datagrid.column_options_guesser';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::COLUMN_OPTIONS_GUESSER_ID)) {
            return;
        }

        $ids = array_keys($container->findTaggedServiceIds(self::COLUMN_OPTIONS_GUESSER_TAG_NAME, true));
        if (!$ids) {
            return;
        }

        $serviceDef = $container->getDefinition(self::COLUMN_OPTIONS_GUESSER_ID);
        $serviceDef->replaceArgument(1, $ids);

        $guessers = [];
        foreach ($ids as $id) {
            $guessers[$id] = new Reference($id);
        }

        $serviceLocator = $container->getDefinition('oro_datagrid.datagrid.guesser_locator');
        $serviceLocator->replaceArgument(0, $guessers);
    }
}
