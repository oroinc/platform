<?php

namespace Oro\Bundle\DataGridBundle\Extension\StoreSql;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

class StoreSqlExtension extends AbstractExtension
{
    const SHOW_SQL_SOURCE = 'show_sql_source';
    const STORED_SQL_PATH = 'metadata[stored_sql]';

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->getDatasourceType() == OrmDatasource::TYPE &&
            $this->getParameters()->get(self::SHOW_SQL_SOURCE, false);
    }

    /**
     * {@inheritdoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $data->offsetAddToArray(MetadataObject::REQUIRED_MODULES_KEY, ['orodatagrid/js/show-sql-source-builder']);
        $data->offsetAddToArray(MetadataObject::OPTIONS_KEY, [self::SHOW_SQL_SOURCE => true]);
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
        $result->offsetAddToArrayByPath(
            self::STORED_SQL_PATH,
            $config->offsetGetByPath(self::STORED_SQL_PATH)
        );
    }
}
