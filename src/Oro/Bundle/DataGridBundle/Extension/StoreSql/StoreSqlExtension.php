<?php

namespace Oro\Bundle\DataGridBundle\Extension\StoreSql;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

class StoreSqlExtension extends AbstractExtension
{
    const DISPLAY_SQL_SOURCE = 'display_sql_source';
    const STORED_SQL_PATH    = 'metadata[stored_sql]';
    const SQL                = 'sql';
    const STORE_SQL          = 'store_sql';

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->getDatasourceType() == OrmDatasource::TYPE &&
            $this->getParameters()->get(self::DISPLAY_SQL_SOURCE, false);
    }

    /**
     * {@inheritdoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $data->offsetAddToArray(MetadataObject::REQUIRED_MODULES_KEY, ['orodatagrid/js/show-sql-source-builder']);
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $config->offsetAddToArray(MetadataObject::OPTIONS_KEY, [self::STORE_SQL => true]);
    }

    /**
     * {@inheritdoc}
     *
     * Gets stored SQL and parameters prepared by
     * Oro\Bundle\DataGridBundle\EventListener\StoreSqlListener
     * and puts them into metadata of ResultsObject
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $value = $config->offsetGetByPath(self::STORED_SQL_PATH);
        if ($value) {
            $result->offsetAddToArray('metadata', [self::DISPLAY_SQL_SOURCE => true]);
            $result->offsetAddToArrayByPath(
                self::STORED_SQL_PATH,
                $value
            );
        }
    }
}
