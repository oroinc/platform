<?php

namespace Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction\Actions\Merge;


use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponseInterface;

class MergeMassActionHandler implements MassActionHandlerInterface
{

    /**
     * Handle mass action
     *
     * @param MassActionHandlerArgs $args
     *
     * @return MassActionResponseInterface
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $queryBuilder = $args->getResults()->getSource();
        $entities = $queryBuilder->getQuery()->execute();
        return $entities;
    }
}
