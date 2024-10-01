<?php

declare(strict_types=1);

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines the configuration parameters recognized by DataGrid > MassAction.
 */
class DeleteMassActionConfiguration implements ConfigurationInterface
{
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('delete');

        $builder->getRootNode()
            ->canBeDisabled()
            ->end();

        return $builder;
    }
}
