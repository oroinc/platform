<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class GroupingGridListener
{
    /**
     * @var ConfigManager
     */
    protected $config;

    /**
     * @param ConfigManager $config
     */
    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $dataGrid = $event->getDatagrid();
        /** @var OrmDatasource $ormDataSource */
        $ormDataSource = $dataGrid->getDatasource();
        $queryBuilder = $ormDataSource->getQueryBuilder();

        if ($this->config->get('oro_email.use_threads_in_emails')) {
            $queryBuilder->andWhere('e.head = 1');
        }
    }
}
