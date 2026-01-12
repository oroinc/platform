<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

/**
 * Defines the contract for datagrid action providers.
 *
 * Action providers dynamically add actions to datagrid configurations based on runtime
 * conditions, entity types, or other contextual information. This allows for flexible
 * action configuration without hardcoding all actions in YAML files.
 */
interface DatagridActionProviderInterface
{
    /**
     * @param DatagridConfiguration $configuration
     * @return boolean
     */
    public function hasActions(DatagridConfiguration $configuration);

    /**
     * Point to add additional configuration to datagrid config that will provide custom actions.
     */
    public function applyActions(DatagridConfiguration $configuration);
}
