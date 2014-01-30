<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;

class EmailListener
{
    /**
     * @var EmailQueryFactory
     */
    protected $factory;

    /**
     * @param EmailQueryFactory $factory
     */
    public function __construct(EmailQueryFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Add required filters
     *
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        /** @var OrmDatasource $ormDataSource */
        $ormDataSource = $event->getDatagrid()->getDatasource();
        $queryBuilder = $ormDataSource->getQueryBuilder();
        $parameters = $event->getParameters();

        $this->factory->prepareQuery($queryBuilder);

        if (array_key_exists('emailIds', $parameters)) {
            $emailIds = $parameters['emailIds'];
            if (!is_array($emailIds)) {
                $emailIds = explode(',', $emailIds);
            }
            $queryBuilder->andWhere($queryBuilder->expr()->in('e.id', $emailIds));
        }
    }
}
