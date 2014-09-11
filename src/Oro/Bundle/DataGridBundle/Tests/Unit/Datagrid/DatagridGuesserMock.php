<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\ColumnOptionsGuesserInterface;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;

/**
 * Special DatagridGuesser mock used for testing purposes.
 */
class DatagridGuesserMock extends DatagridGuesser
{
    /**
     * @param ColumnOptionsGuesserInterface[] $guessers
     */
    public function __construct(array $guessers = [])
    {
        $container = new ContainerBuilder();

        $ids = ['mock'];
        $container->set('mock', new ColumnOptionsGuesserMock());

        $index = 0;
        foreach ($guessers as $guesser) {
            $id    = sprintf('guesser%d', ++$index);
            $ids[] = $id;
            $container->set($id, $guesser);
        }

        parent::__construct($container, $ids);
    }
}
