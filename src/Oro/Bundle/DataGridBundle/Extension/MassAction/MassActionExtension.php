<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface;

class MassActionExtension extends ActionExtension
{
    const ACTION_KEY          = 'mass_actions';
    const METADATA_ACTION_KEY = 'massActions';

    /** @var array */
    protected $actions = [];

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        // Applicable due to the possibility of dynamically add mass action
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $result->offsetAddToArray(
            'metadata',
            [
                static::METADATA_ACTION_KEY => $this->getActionsMetadata($config)
            ]
        );
    }

    /**
     * Get grid mass action by name
     *
     * @param string           $name
     * @param DatagridInterface $datagrid
     *
     * @return bool|ActionInterface
     */
    public function getMassAction($name, DatagridInterface $datagrid)
    {
        $config = $datagrid->getAcceptor()->getConfig();

        $action = false;
        if (isset($config[static::ACTION_KEY][$name])) {
            $action = $this->getActionObject($name, $config[static::ACTION_KEY][$name]);
        }

        return $action;
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority()
    {
        // should be applied before action extension
        return 205;
    }
}
