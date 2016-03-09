<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\ActionBundle\Exception\CircularReferenceException;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ArraySubstitution;

class ActionRegistry
{
    const DEFAULT_GROUP = '';

    /** @var ActionConfigurationProvider */
    protected $configurationProvider;

    /** @var ActionAssembler */
    protected $assembler;

    /** @var ApplicationsHelper */
    protected $applicationsHelper;

    /** @var array|Action[] */
    protected $actions;

    /* @var ArraySubstitution */
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
        $this->substitution = new ArraySubstitution();
    }

    /**
     * @param string|null $entityClass match by entity
     * @param string|null $route match by route
     * @param string|null $datagrid match by grid
     * @param string|array|null $group filter by group
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
                $this->isDatagridMatched($datagrid, $definition) ||
                ($route && in_array($route, $definition->getRoutes(), true))
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
     * @param string|array|null $group
     * @return array|Action[]
     */
    protected function filterByGroup($group = null)
    {
        $this->normalizeGroup($group);

        return array_filter($this->actions, function (Action $action) use ($group) {
            $matchedGroups = array_intersect($group, $action->getDefinition()->getGroups() ?: [static::DEFAULT_GROUP]);
            return !empty($matchedGroups);
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

    /**
     * @param string $datagrid
     * @param ActionDefinition $definition
     * @return bool
     */
    protected function isDatagridMatched($datagrid, ActionDefinition $definition)
    {
        if (!$datagrid) {
            return false;
        }

        $forAllDatagrids = $definition->isForAllDatagrids();

        return $forAllDatagrids || $datagrid && in_array($datagrid, $definition->getDatagrids(), true);
    }

    /**
     * @param string|array|null $group
     */
    protected function normalizeGroup(&$group)
    {
        if (!is_array($group)) {
            $group = empty($group) ? [static::DEFAULT_GROUP] : [(string)$group];
        } else {
            foreach ($group as $key => $value) {
                $group[$key] = (string)$value;
            }
        }
    }
}
