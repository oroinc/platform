<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class MassActionExtension extends AbstractExtension
{
    const METADATA_ACTION_KEY = 'massActions';
    const ACTION_KEY          = 'mass_actions';

    /** @var MassActionFactory */
    protected $actionFactory;

    /** @var MassActionMetadataFactory */
    protected $actionMetadataFactory;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var bool */
    protected $isMetadataVisited = false;

    /**
     * @param MassActionFactory         $actionFactory
     * @param MassActionMetadataFactory $actionMetadataFactory
     * @param SecurityFacade            $securityFacade
     */
    public function __construct(
        MassActionFactory $actionFactory,
        MassActionMetadataFactory $actionMetadataFactory,
        SecurityFacade $securityFacade
    ) {
        $this->actionFactory = $actionFactory;
        $this->actionMetadataFactory = $actionMetadataFactory;
        $this->securityFacade = $securityFacade;
    }

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
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $this->isMetadataVisited = true;
        $data->offsetAddToArray(self::METADATA_ACTION_KEY, $this->getActionsMetadata($config));
    }

    /**
     * {@inheritDoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        if (!$this->isMetadataVisited) {
            $result->offsetAddToArray(
                'metadata',
                [self::METADATA_ACTION_KEY => $this->getActionsMetadata($config)]
            );
        }
    }

    /**
     * Gets grid mass action by name.
     *
     * @param string            $name
     * @param DatagridInterface $datagrid
     *
     * @return MassActionInterface|null
     */
    public function getMassAction($name, DatagridInterface $datagrid)
    {
        $config = $datagrid->getAcceptor()->getConfig();
        if (!isset($config[self::ACTION_KEY][$name])) {
            return null;
        }

        return $this->createAction($name, $config[self::ACTION_KEY][$name]);
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority()
    {
        /**
         * should be applied before action extension
         * @see \Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension::getPriority
         */
        return 205;
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return array
     */
    protected function getActionsMetadata(DatagridConfiguration $config)
    {
        $actionsMetadata = [];
        $actions = $config->offsetGetOr(self::ACTION_KEY, []);
        foreach ($actions as $actionName => $actionConfig) {
            $action = $this->createAction($actionName, $actionConfig);
            if (null !== $action) {
                $actionsMetadata[$action->getName()] = $this->createActionMetadata($action);
            }
        }

        return $actionsMetadata;
    }

    /**
     * @param string $actionName
     * @param array  $actionConfig
     *
     * @return MassActionInterface|null
     */
    protected function createAction($actionName, array $actionConfig)
    {
        $action = $this->actionFactory->createAction($actionName, $actionConfig);

        $aclResource = $action->getAclResource();
        if ($aclResource && !$this->securityFacade->isGranted($aclResource)) {
            $action = null;
        }

        return $action;
    }

    /**
     * @param MassActionInterface $action
     *
     * @return array
     */
    protected function createActionMetadata(MassActionInterface $action)
    {
        return $this->actionMetadataFactory->createActionMetadata($action);
    }
}
