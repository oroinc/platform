<?php

namespace Oro\Bundle\DataGridBundle\Tools;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class GridConfigurationHelper
{
    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /**
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct(EntityClassResolver $entityClassResolver)
    {
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return string|null
     */
    public function getEntity(DatagridConfiguration $config)
    {
        $entityClassName = $config->offsetGetByPath('[extended_entity_name]');
        if ($entityClassName) {
            return $entityClassName;
        }

        $from = $config->offsetGetByPath('[source][query][from]');
        if (!$from) {
            return null;
        }

        return $this->entityClassResolver->getEntityClass($from[0]['table']);
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return null
     */
    public function getEntityRootAlias(DatagridConfiguration $config)
    {
        $from = $config->offsetGetByPath('[source][query][from]');
        if ($from) {
            return $from[0]['alias'];
        }

        return null;
    }
}
