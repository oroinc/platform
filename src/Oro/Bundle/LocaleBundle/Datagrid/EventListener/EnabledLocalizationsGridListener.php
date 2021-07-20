<?php

namespace Oro\Bundle\LocaleBundle\Datagrid\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;

/**
 * This listener provides enabled localizations ids to the grid.
 */
class EnabledLocalizationsGridListener
{
    /**
     * @var ConfigManager $configManager
     */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function onBuildAfter(BuildAfter $event)
    {
        $datasource = $event->getDatagrid()->getDatasource();
        if (!$datasource instanceof OrmDatasource) {
            return;
        }

        $enabledLocalizationIds = (array) $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS),
            false,
            false,
            $this->getScopeIdentifier($event->getDatagrid())
        );

        $dataGridQueryBuilder = $datasource->getQueryBuilder();
        $dataGridQueryBuilder->setParameter('ids', $enabledLocalizationIds);
    }

    /**
     * @param DatagridInterface $datagrid
     * @return null
     */
    protected function getScopeIdentifier(DatagridInterface $datagrid)
    {
        return null;
    }
}
