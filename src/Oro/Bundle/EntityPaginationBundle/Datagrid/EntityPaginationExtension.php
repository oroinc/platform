<?php

namespace Oro\Bundle\EntityPaginationBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

/**
 * Datagrid extension that enables and validates entity pagination configuration.
 *
 * This extension processes datagrid configurations to enable entity pagination functionality.
 * It validates that the `entity_pagination` option is a boolean value and applies it to the
 * datagrid configuration. The extension is only applicable to ORM-based datagrids that have
 * entity pagination explicitly enabled in their configuration.
 */
class EntityPaginationExtension extends AbstractExtension
{
    const ENTITY_PAGINATION_PATH = '[options][entity_pagination]';
    const ENTITY_PAGINATION_TARGET_PATH = '[options][entity_pagination_target]';

    #[\Override]
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && $config->isOrmDatasource()
            && null !== $config->offsetGetByPath(self::ENTITY_PAGINATION_PATH);
    }

    #[\Override]
    public function processConfigs(DatagridConfiguration $config)
    {
        $pagination = $config->offsetGetByPath(self::ENTITY_PAGINATION_PATH);

        if ($pagination !== null && !is_bool($pagination)) {
            throw new \LogicException('Entity pagination is not boolean');
        }

        $config->offsetSetByPath(self::ENTITY_PAGINATION_PATH, (bool)$pagination);
    }
}
