<?php

namespace Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class GuessPass implements CompilerPassInterface
{
    const COLUMN_OPTIONS_GUESSER_ID       = 'oro_datagrid.datagrid.guesser';
    const COLUMN_OPTIONS_GUESSER_TAG_NAME = 'oro_datagrid.column_options_guesser';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::COLUMN_OPTIONS_GUESSER_ID)) {
            $serviceDef = $container->getDefinition(self::COLUMN_OPTIONS_GUESSER_ID);
            $guessers = array_keys($container->findTaggedServiceIds(self::COLUMN_OPTIONS_GUESSER_TAG_NAME));
            $serviceDef->replaceArgument(1, $guessers);
        }
    }
}
