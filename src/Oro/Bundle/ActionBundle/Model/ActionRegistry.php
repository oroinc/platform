<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\ActionBundle\Exception\ActionReferenceException;
use Oro\Bundle\ActionBundle\Exception\CircularReferenceException;
use Oro\Bundle\ActionBundle\Helper\ActionSubstitutionHelper;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\SubstitutionVenue;

class ActionRegistry
{
    /** @var ActionConfigurationProvider */
    protected $configurationProvider;

    /** @var ActionAssembler */
    protected $assembler;

    /** @var ApplicationsHelper */
    protected $applicationsHelper;

    /** @var array|Action[] */
    protected $actions;

    /**
     * @var SubstitutionVenue
     */
    protected $substitution;

    /**
     * @param ActionConfigurationProvider $configurationProvider
     * @param ActionAssembler $assembler
     * @param ApplicationsHelper $applicationsHelper
     */
    public function __construct(
        ActionConfigurationProvider $configurationProvider,
        ActionAssembler $assembler,
        ApplicationsHelper $applicationsHelper
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->assembler = $assembler;
        $this->applicationsHelper = $applicationsHelper;
        $this->substitution = new SubstitutionVenue();
    }

    /**
     * @param string|null $entityClass match by entity
     * @param string|null $route match by route
     * @param string|null $datagrid match by grid
     * @param string|null $group filter by group
     * @return Action[]
     */
    public function find($entityClass, $route, $datagrid, $group = null)
    {
        $this->loadActions();

        $allActions = $this->filterByGroup($group);
        $actions = [];

        foreach ($allActions as $action) {
            $definition = $action->getDefinition();

            if ($this->isEntityClassMatched($entityClass, $definition) ||
                $action->hasUnboundSubstitution() ||
                ($route && in_array($route, $definition->getRoutes(), true)) ||
                ($datagrid && in_array($datagrid, $definition->getDatagrids(), true))
            ) {
                $actions[$action->getName()] = $action;
            }
        }

        $this->substitution->apply($actions);

        return $actions;
    }

    /**
     * @param string $name
     * @return null|Action
     */
    public function findByName($name)
    {
        $this->loadActions();

        return array_key_exists($name, $this->actions) ? $this->actions[$name] : null;
    }

    /**
     * @throws CircularReferenceException
     */
    protected function loadActions()
    {
        if ($this->actions !== null) {
            return;
        }

        $this->actions = [];

        $replacements = [];

        $configuration = $this->configurationProvider->getActionConfiguration();
        $actions = $this->assembler->assemble($configuration);

        foreach ($actions as $action) {
            if (!$action->isEnabled()) {
                continue;
            }

            if (!$this->applicationsHelper->isApplicationsValid($action)) {
                continue;
            }

            $actionName = $action->getName();

            $this->actions[$actionName] = $action;

            $substitutionTarget = $action->getDefinition()->getSubstituteAction();
            if ($substitutionTarget) {
                $replacements[$substitutionTarget] = $actionName;
            }
        }

        $substitutionMap = [];
        foreach ($replacements as $target => $replacementName) {
            if (array_key_exists($target, $this->actions)) {
                $substitutionMap[$target] = $replacementName;
            } else {
                unset($this->actions[$replacementName]); //if nothing to replace no need to keep the action
            }
        }

        $this->substitution->setMap($substitutionMap);

    }

    /**
     * @param string|null $group
     * @return array|Action[]
     */
    protected function filterByGroup($group = null)
    {
        return array_filter($this->actions, function (Action $action) use ($group) {
            return $group
                ? in_array($group, $action->getDefinition()->getGroups(), true)
                : !$action->getDefinition()->getGroups();
        });
    }

    /**
     * @param string $className
     * @param ActionDefinition $definition
     * @return bool
     */
    protected function isEntityClassMatched($className, ActionDefinition $definition)
    {
        if (!$className) {
            return false;
        }

        $forAllEntities = $definition->isForAllEntities();

        $inEntities = in_array($className, $definition->getEntities(), true);
        $inExcludedEntities = in_array($className, $definition->getExcludeEntities(), true);

        return ($forAllEntities && !$inExcludedEntities) || (!$forAllEntities && $inEntities);
    }
}
