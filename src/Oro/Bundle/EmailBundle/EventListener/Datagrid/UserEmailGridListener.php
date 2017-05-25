<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;

class UserEmailGridListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /** @var  EmailQueryFactory */
    protected $emailQueryFactory;

    /**
     * @param EmailQueryFactory $emailQueryFactory
     */
    public function __construct(EmailQueryFactory $emailQueryFactory)
    {
        $this->emailQueryFactory = $emailQueryFactory;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $datagrid = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        if ($datasource instanceof OrmDatasource) {
            $queryBuilder = $datasource->getQueryBuilder();
            $countQueryBuilder = $datasource->getCountQb();
            $this->emailQueryFactory->applyAcl($queryBuilder);
            $this->emailQueryFactory->applyAcl($countQueryBuilder);
        }
    }
}
