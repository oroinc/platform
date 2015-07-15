<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;

class EmailGridListener
{
    /**
     * @var EmailQueryFactory
     */
    protected $factory;

    /**
     * @var bool
     */
    protected $singleMailboxMode;

    /**
     * @param EmailQueryFactory $factory
     * @param bool $singleMailboxMode
     */
    public function __construct(EmailQueryFactory $factory, $singleMailboxMode)
    {
        $this->factory = $factory;
        $this->singleMailboxMode = $singleMailboxMode;
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
        $parameters = $event->getDatagrid()->getParameters();

        $this->factory->filterQueryByUserId($queryBuilder, $parameters->get('userId'));

        if ($parameters->has('emailIds')) {
            $emailIds = $parameters->get('emailIds');
            if (!is_array($emailIds)) {
                $emailIds = explode(',', $emailIds);
            }
            $queryBuilder->andWhere($queryBuilder->expr()->in('e.id', $emailIds));
        }

        if ($this->singleMailboxMode) {
            // remove mailbox name column
            $config = $event->getDatagrid()->getConfig();
            if (isset($config['columns']['mailbox'])) {
                $config->offsetUnsetByPath('[columns][mailbox]');
            }

            // add isActive filter
            $queryBuilder->leftJoin('f.origin', 'o')
                ->andWhere('o.isActive = :isActive')
                ->setParameter('isActive', true);
        }
    }
}
