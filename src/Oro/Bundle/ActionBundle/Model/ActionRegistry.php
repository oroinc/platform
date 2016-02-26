<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\ActionBundle\Exception\ActionReferenceException;
use Oro\Bundle\ActionBundle\Helper\ActionSubstitutionHelper;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;

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
     * Substitutions map
     * @var array
     */
    protected $substitutions;

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
    }

    /**
     * @param string|null $entityClass
     * @param string|null $route
     * @param string|null $datagrid
     * @param string|null $group
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
                ($route && in_array($route, $definition->getRoutes(), true)) ||
                ($datagrid && in_array($datagrid, $definition->getDatagrids(), true)) ||
                $action->hasUnboundSubstitution()
            ) {
                $actions[$action->getName()] = $action;
            }
        }

        $this->applySubstitutions($actions);

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
     * @throws ActionReferenceException
     */
    protected function loadActions()
    {
        if ($this->actions !== null) {
            return;
        }

        $this->actions = [];

        $substitutions = [];

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
                $substitutions[$substitutionTarget] = $actionName;
            }
        }

        $this->substitutions = [];

        foreach ($substitutions as $target => $replacementName) {
            if (array_key_exists($target, $this->actions)) {
                $this->substitutions[$target] = $replacementName;
            } else {
                unset($this->actions[$replacementName]); //if nothing to replace no need to keep the action
            }
        }

        //circular references protection - throws an exception if found (this check can be moved to compiler pass)
        ActionSubstitutionHelper::detectCircularSubstitutions($this->substitutions);

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

        if (($forAllEntities && !$inExcludedEntities) || (!$forAllEntities && $inEntities)) {
            return true;
        }

        return false;
    }

    /**
     * @param array $actions
     */
    protected function applySubstitutions(array &$actions)
    {
        if ($this->substitutions) {
            ActionSubstitutionHelper::applySubstitutions($this->substitutions, $actions);
        }
    }
}
