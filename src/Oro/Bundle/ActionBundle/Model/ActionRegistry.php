<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;

class ActionRegistry
{
    const DEFAULT_GROUP = '';

    /** @var ActionConfigurationProvider */
    protected $configurationProvider;

    /** @var ActionAssembler */
    protected $assembler;

    /** @var ApplicationsHelper */
    protected $applicationsHelper;

    /** @var array|Operation[] */
    protected $operations;

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
     * @param string|array|null $group
     * @return Operation[]
     */
    public function find($entityClass, $route, $datagrid, $group = null)
    {
        $this->loadActions();

        $allOperations = $this->filterByGroup($group);
        $operations = [];

        foreach ($allOperations as $operation) {
            $definition = $operation->getDefinition();

            if ($this->isEntityClassMatched($entityClass, $definition) ||
                ($route && in_array($route, $definition->getRoutes(), true)) ||
                $this->isDatagridMatched($datagrid, $definition)
            ) {
                $operations[$operation->getName()] = $operation;
            }
        }

        return $operations;
    }

    /**
     * @param string $name
     * @return null|Operation
     */
    public function findByName($name)
    {
        $this->loadActions();

        return array_key_exists($name, $this->operations) ? $this->operations[$name] : null;
    }

    protected function loadActions()
    {
        if ($this->operations !== null) {
            return;
        }

        $this->operations = [];

        $configuration = $this->configurationProvider->getActionConfiguration();
        $operations = $this->assembler->assemble($configuration);

        foreach ($operations as $operation) {
            if (!$operation->isEnabled()) {
                continue;
            }

            if (!$this->applicationsHelper->isApplicationsValid($operation)) {
                continue;
            }

            $this->operations[$operation->getName()] = $operation;
        }
    }

    /**
     * @param string|array|null $group
     * @return array|Operation[]
     */
    protected function filterByGroup($group = null)
    {
        $this->normalizeGroup($group);

        return array_filter($this->operations, function (Operation $operation) use ($group) {
            $matchedGroups = array_intersect(
                $group,
                $operation->getDefinition()->getGroups() ?: [static::DEFAULT_GROUP]
            );
            return !empty($matchedGroups);
        });
    }

    /**
     * @param string $className
     * @param OperationDefinition $definition
     * @return bool
     */
    protected function isEntityClassMatched($className, OperationDefinition $definition)
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
     * @param string $datagrid
     * @param OperationDefinition $definition
     * @return bool
     */
    protected function isDatagridMatched($datagrid, OperationDefinition $definition)
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
