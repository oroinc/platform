<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ActionRegistry
{
    /** @var ActionConfigurationProvider */
    protected $configurationProvider;

    /** @var ActionAssembler */
    protected $assembler;

    /** @var ApplicationsHelper */
    protected $applicationsHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var array|Action[] */
    protected $actions;

    /** @var array */
    protected $shortEntityNames = [];

    /**
     * @param ActionConfigurationProvider $configurationProvider
     * @param ActionAssembler $assembler
     * @param ApplicationsHelper $applicationsHelper
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ActionConfigurationProvider $configurationProvider,
        ActionAssembler $assembler,
        ApplicationsHelper $applicationsHelper,
        DoctrineHelper $doctrineHelper
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->assembler = $assembler;
        $this->applicationsHelper = $applicationsHelper;
        $this->doctrineHelper = $doctrineHelper;
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
                ($datagrid && in_array($datagrid, $definition->getDatagrids(), true))
            ) {
                $actions[$action->getName()] = $action;
            }
        }

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

    protected function loadActions()
    {
        if ($this->actions !== null) {
            return;
        }

        $this->actions = [];

        $configuration = $this->configurationProvider->getActionConfiguration();
        $actions = $this->assembler->assemble($configuration);

        foreach ($actions as $action) {
            if (!$action->isEnabled()) {
                continue;
            }

            if (!$this->applicationsHelper->isApplicationsValid($action)) {
                continue;
            }

            $this->actions[$action->getName()] = $action;
        }
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

        if ((!$forAllEntities && in_array($className, $this->filterEntities($definition->getEntities()), true)) ||
            ($forAllEntities && !in_array($className, $this->filterEntities($definition->getExcludeEntities()), true))
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param array $entities
     * @return array
     */
    protected function filterEntities(array $entities)
    {
        return array_filter(array_map([$this, 'getEntityClassName'], $entities), 'is_string');
    }

    /**
     * @param string $entityName
     * @return string|bool
     */
    protected function getEntityClassName($entityName)
    {
        if (!array_key_exists($entityName, $this->shortEntityNames)) {
            $this->shortEntityNames[$entityName] = null;

            try {
                $entityClass = $this->doctrineHelper->getEntityClass($entityName);

                if (class_exists($entityClass, true)) {
                    $reflection = new \ReflectionClass($entityClass);

                    $this->shortEntityNames[$entityName] = $reflection->getName();
                }
            } catch (\Exception $e) {
            }
        }

        return $this->shortEntityNames[$entityName];
    }
}
