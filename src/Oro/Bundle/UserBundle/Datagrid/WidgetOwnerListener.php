<?php

namespace Oro\Bundle\UserBundle\Datagrid;

use Doctrine\ORM\Query;

use Oro\Component\DoctrineUtils\ORM\QueryUtils;

use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\UserBundle\Dashboard\OwnerHelper;

use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;

class WidgetOwnerListener
{
    /** @var OwnerHelper */
    protected $ownerHelper;

    /** @var WidgetConfigs */
    protected $widgetConfigs;

    /** @var string */
    protected $ownerField;

    /**
     * @param OwnerHelper   $ownerHelper
     * @param WidgetConfigs $widgetConfigs
     * @param string        $ownerField
     */
    public function __construct(OwnerHelper $ownerHelper, WidgetConfigs $widgetConfigs, $ownerField)
    {
        $this->ownerHelper   = $ownerHelper;
        $this->widgetConfigs = $widgetConfigs;
        $this->ownerField    = $ownerField;
    }

    /**
     * @param OrmResultBefore $event
     */
    public function onResultBefore(OrmResultBefore $event)
    {
        $widgetOptions = $this->widgetConfigs->getWidgetOptions();
        $ids           = $this->ownerHelper->getOwnerIds($widgetOptions);
        if ($ids) {
            /** @var OrmDatasource $dataSource */
            $dataSource  = $event->getDatagrid()->getDatasource();
            $qb          = $dataSource->getQueryBuilder();
            $rootAliases = $qb->getRootAliases();
            $field       = sprintf('%s.%s', reset($rootAliases), $this->ownerField);
            QueryUtils::applyOptimizedIn($qb, $field, $ids);

            /** @var Query $query */
            $query = $event->getQuery();
            $query->setDQL($dataSource->getQueryBuilder()->getQuery()->getDQL());
            $queryParameters = $query->getParameters();
            foreach ($qb->getParameters() as $parameter) {
                $queryParameters->add($parameter);
            }
        }
    }
}
