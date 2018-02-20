<?php

namespace Oro\Bundle\EntityPaginationBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

class EntityPaginationExtension extends AbstractExtension
{
    const ENTITY_PAGINATION_PATH = '[options][entity_pagination]';
    const ENTITY_PAGINATION_TARGET_PATH = '[options][entity_pagination_target]';

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && $config->isOrmDatasource()
            && null !== $config->offsetGetByPath(self::ENTITY_PAGINATION_PATH);
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
