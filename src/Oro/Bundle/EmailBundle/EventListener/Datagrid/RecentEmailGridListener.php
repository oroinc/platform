<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailGridHelper;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;

class RecentEmailGridListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /** @var EmailGridHelper */
    protected $emailGridHelper;

    /** @var  EmailQueryFactory */
    protected $emailQueryFactory;

    /**
     * @param EmailGridHelper        $emailGridHelper
     * @param EmailQueryFactory|null $emailQueryFactory
     */
    public function __construct(EmailGridHelper $emailGridHelper, EmailQueryFactory $emailQueryFactory = null)
    {
        $this->emailGridHelper   = $emailGridHelper;
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

        $datagrid   = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        if ($datasource instanceof OrmDatasource) {
            $queryBuilder = $datasource->getQueryBuilder();

            $this->emailQueryFactory->applyAcl($queryBuilder);
            $this->emailQueryFactory->prepareQuery($queryBuilder);
        }
    }
}
