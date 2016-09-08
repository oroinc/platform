<?php

namespace Oro\Bundle\EntityPaginationBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class EntityPaginationExtension extends AbstractExtension
{
    const ENTITY_PAGINATION_PATH = '[options][entity_pagination]';
    const ENTITY_PAGINATION_TARGET_PATH = '[options][entity_pagination_target]';

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $pagination = $config->offsetGetByPath(self::ENTITY_PAGINATION_PATH);

        if ($pagination === null) {
            return false;
        }

        return $config->getDatasourceType() === OrmDatasource::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $pagination = $config->offsetGetByPath(self::ENTITY_PAGINATION_PATH);

        if ($pagination !== null && !is_bool($pagination)) {
            throw new \LogicException('Entity pagination is not boolean');
        }

        $config->offsetSetByPath(self::ENTITY_PAGINATION_PATH, (bool)$pagination);
    }
}
