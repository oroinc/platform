<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ArraySubstitution;
use Oro\Bundle\ActionBundle\Model\Assembler\OperationAssembler;

class OperationRegistry
{
    const DEFAULT_GROUP = '';

    /** @var ConfigurationProviderInterface */
    protected $configurationProvider;

    /** @var OperationAssembler */
    protected $assembler;

    /** @var ApplicationsHelper */
    protected $applicationsHelper;

    /** @var array|Operation[] */
    protected $operations;

    /* @var ArraySubstitution */
    protected $substitution;

    /**
     * @param ConfigurationProviderInterface $configurationProvider
     * @param OperationAssembler $assembler
     * @param ApplicationsHelper $applicationsHelper
     */
    public function __construct(
        ConfigurationProviderInterface $configurationProvider,
        OperationAssembler $assembler,
        ApplicationsHelper $applicationsHelper
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->assembler = $assembler;
        $this->applicationsHelper = $applicationsHelper;
        $this->substitution = new ArraySubstitution();
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
        $this->loadOperations();

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

        $this->substitution->apply($operations);

        return $operations;
    }

    /**
     * @param string $name
     * @return null|Operation
     */
    public function findByName($name)
    {
        $this->loadOperations();

        return array_key_exists($name, $this->operations) ? $this->operations[$name] : null;
    }

    protected function loadOperations()
    {
        if ($this->operations !== null) {
            return;
        }

        $this->operations = [];

        $replacements = [];

        $configuration = $this->configurationProvider->getConfiguration();
        $operations = $this->assembler->assemble($configuration);

        foreach ($operations as $operation) {
            if (!$operation->isEnabled()) {
                continue;
            }

            if (!$this->applicationsHelper->isApplicationsValid($operation)) {
                continue;
            }

            $operationName = $operation->getName();

            $this->operations[$operationName] = $operation;

            $substitutionTarget = $operation->getDefinition()->getSubstituteOperation();
            if ($substitutionTarget) {
                $replacements[$substitutionTarget] = $operationName;
            }
        }

        $this->substitution->setMap($replacements);
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

            return 0 !== count($matchedGroups);
        });
    }

    /**
     * @param string $className
     * @param OperationDefinition $definition
     * @return bool
     */
    protected function isEntityClassMatched($className, OperationDefinition $definition)
    {
        return $this->match(
            $className,
            $definition->getEntities(),
            $definition->getExcludeEntities(),
            $definition->isForAllEntities()
        );
    }

    /**
     * @param string $datagrid
     * @param OperationDefinition $definition
     * @return bool
     */
    protected function isDatagridMatched($datagrid, OperationDefinition $definition)
    {
        return $this->match(
            $datagrid,
            $definition->getDatagrids(),
            $definition->getExcludeDatagrids(),
            $definition->isForAllDatagrids()
        );
    }

    /**
     * @param string $value
     * @param array $inclusion
     * @param array $exclusion
     * @param bool $forAll
     * @return bool
     */
    protected function match($value, array $inclusion, array $exclusion, $forAll)
    {
        if (!$value) {
            return false;
        }

        return ($forAll && !in_array($value, $exclusion, true)) || (!$forAll && in_array($value, $inclusion, true));
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
