<?php

namespace Oro\Bundle\GridBundle\Action\MassAction;

use Oro\Bundle\GridBundle\Action\MassAction\MassActionInterface;

use Oro\Bundle\GridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\GridBundle\Datagrid\IterableResultInterface;
use Oro\Bundle\GridBundle\Datagrid\ResultRecordInterface;
use Symfony\Component\HttpFoundation\Request;

interface MassActionMediatorInterface
{
    /**
     * @return MassActionInterface
     */
    public function getMassAction();

    /**
     * @return IterableResultInterface|ResultRecordInterface[]
     */
    public function getResults();

    /**
     * @return array
     */
    public function getData();

    /**
     * @return DatagridInterface
     */
    public function getDatagrid();
}
