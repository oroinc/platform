<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Data grid mass action extension
 */
class MassActionExtension extends AbstractExtension
{
    const METADATA_ACTION_KEY = 'massActions';
    const ACTION_KEY          = 'mass_actions';
    const OPTIONS_KEY         = 'options';
    const OPTIONS_PATH        = '[options][mass_actions]';
    const ALLOWED_REQUEST_TYPES   = 'allowedRequestTypes';
    const ALLOWED_REQUEST_METHODS = ['GET', 'POST', 'DELETE', 'PUT', 'PATCH'];

    /** @var MassActionFactory */
    protected $actionFactory;

    /** @var MassActionMetadataFactory */
    protected $actionMetadataFactory;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var bool */
    protected $isMetadataVisited = false;

    /** {@inheritdoc} */
    protected $excludedModes = [
        DatagridModeProvider::DATAGRID_IMPORTEXPORT_MODE
    ];

    public function __construct(
        MassActionFactory $actionFactory,
        MassActionMetadataFactory $actionMetadataFactory,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->actionFactory = $actionFactory;
        $this->actionMetadataFactory = $actionMetadataFactory;
        $this->authorizationChecker = $authorizationChecker;
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
        if ($actionConfig[PropertyInterface::DISABLED_KEY] ?? false) {
            return null;
        }

        $action = $this->actionFactory->createAction($actionName, $actionConfig);
        $configuredTypes = $action->getOptions()->offsetGetByPath(self::ALLOWED_REQUEST_TYPES);

        if ($configuredTypes) {
            $foundTypes = array_intersect(array_map('strtoupper', $configuredTypes), self::ALLOWED_REQUEST_METHODS);
            if (count($foundTypes) !== count($configuredTypes)) {
                throw new RuntimeException(
                    sprintf(
                        'Action parameter "%s" contains wrong HTTP method. Given "%s", allowed: "%s".',
                        self::ALLOWED_REQUEST_TYPES,
                        implode(', ', $configuredTypes),
                        implode(', ', self::ALLOWED_REQUEST_METHODS)
                    )
                );
            }
        }

        $aclResource = $action->getAclResource();
        if ($aclResource && !$this->authorizationChecker->isGranted($aclResource)) {
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
