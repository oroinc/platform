<?php

namespace Oro\Bundle\GridBundle\Builder\ORM;

use Oro\Bundle\GridBundle\Builder\AbstractDatagridBuilder;
use Oro\Bundle\GridBundle\Datagrid\ORM\Pager;
use Oro\Bundle\GridBundle\Datagrid\PagerInterface;
use Oro\Bundle\GridBundle\Datagrid\ProxyQueryInterface;

class DatagridBuilder extends AbstractDatagridBuilder
{
    /**
     * @param ProxyQueryInterface $query
     * @return PagerInterface
     */
    protected function createPager(ProxyQueryInterface $query)
    {
        $pager = new Pager();
        $pager->setQuery($query);
        return $pager;
    }
}
