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
    public function onBuildBefore(BuildBefore $event)
    {
        if ($this->config->get('oro_activity_list.grouping')) {
            $config = $event->getConfig();

            $config->offsetAddToArray('actions', [
                'test' => [
                    'type'  => 'navigate',
                    'label' => 'oro.grid.action.view',
                    'link'  => 'view_thread_link',
                    'icon'  => 'eye-open',
                ]
            ]);
        }
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

        /**
         * todo this is not obvious config name for grouping emails
         */
        if ($this->config->get('oro_activity_list.grouping')) {
            $queryBuilder->andWhere('e.head = 1');
        }
    }
}
