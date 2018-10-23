<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\ORM\ORMException;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface;
use Oro\Bundle\ActionBundle\Helper\ArraySubstitution;
use Oro\Bundle\ActionBundle\Model\Assembler\OperationAssembler;
use Oro\Bundle\ActionBundle\Model\Criteria\OperationFindCriteria;
use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Registry that returns the Registry.
 */
class OperationRegistry
{
    /** @var ConfigurationProviderInterface */
    protected $configurationProvider;

    /** @var OperationAssembler */
    protected $assembler;

    /** @var CurrentApplicationProviderInterface */
    protected $applicationProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var array|Operation[] */
    protected $operations;

    /** @var ArraySubstitution */
    protected $substitution;

    /** @var array */
    protected $configuration;

    /** @var array */
    private $entityNames = [];

    /** @var OperationRegistryFilterInterface[] */
    private $filters = [];

    /**
     * @param ConfigurationProviderInterface $configurationProvider
     * @param OperationAssembler $assembler
     * @param CurrentApplicationProviderInterface $applicationProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ConfigurationProviderInterface $configurationProvider,
        OperationAssembler $assembler,
        CurrentApplicationProviderInterface $applicationProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->assembler = $assembler;
        $this->applicationProvider = $applicationProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->substitution = new ArraySubstitution();
    }

    /**
     * @param OperationFindCriteria $criteria
     * @return Operation[]
     */
    public function find(OperationFindCriteria $criteria)
    {
        $this->loadConfiguration();

        $configurations = $this->filterByGroup($criteria->getGroups());

        /** @var Operation[] $operations */
        $operations = [];
        $replacements = [];

        foreach ($configurations as $name => $config) {
            if (!$this->isApplicable($config, $criteria)) {
                continue;
            }

            if (!isset($this->operations[$name])) {
                $this->operations[$name] = $this->assembler->createOperation($name, $config);
            }

            $operations[$name] = $this->operations[$name];

            $substitutionTarget = $operations[$name]->getDefinition()->getSubstituteOperation();
            if ($substitutionTarget) {
                $replacements[$substitutionTarget] = $name;
            }
        }

        $this->substitution->setMap($replacements);
        $this->substitution->apply($operations);

        return $this->filter($operations, $criteria);
    }

    /**
     * @param string $name
     * @param OperationFindCriteria $criteria
     *
     * @return null|Operation
     */
    public function findByName($name, OperationFindCriteria $criteria = null)
    {
        $this->loadConfiguration();

        $operation = null;
        if (array_key_exists($name, $this->operations)) {
            $operation = $this->operations[$name];

            if (!$operation instanceof Operation) {
                $operation = $this->assembler->createOperation($name, $this->configuration[$name]);

                $this->operations[$name] = $operation;
            }
        }

        if ($operation && $criteria &&
            (!$this->isApplicable($this->configuration[$name], $criteria) || !$this->filter([$operation], $criteria))
        ) {
            $operation = null;
        }

        return $operation;
    }

    protected function loadConfiguration()
    {
        if ($this->configuration !== null && $this->operations !== null) {
            return;
        }

        $this->configuration = $this->configurationProvider->getConfiguration();
        $this->operations = array_fill_keys(array_keys($this->configuration), null);
    }

    /**
     * @param array $expectedGroups
     * @return array
     */
    protected function filterByGroup(array $expectedGroups)
    {
        return array_filter($this->configuration, function (array $operation) use ($expectedGroups) {
            $groups = (array)$operation['groups'] ?: [ButtonInterface::DEFAULT_GROUP];

            return 0 !== count(array_intersect($expectedGroups, $groups));
        });
    }

    /**
     * @param array $config
     * @param OperationFindCriteria $findCriteria
     * @return bool
     */
    protected function isApplicable(array $config, OperationFindCriteria $findCriteria)
    {
        if (!(bool)$config['enabled']) {
            return false;
        }

        if (!$this->applicationProvider->isApplicationsValid((array)$config['applications'])) {
            return false;
        }

        $applicable = $this->isEntityClassMatched($findCriteria, $config) ||
            $this->isRouteMatched($findCriteria, $config) ||
            $this->isDatagridMatched($findCriteria, $config);

        return $applicable && $this->isNotExcluded($findCriteria->getDatagrid(), (array)$config['exclude_datagrids']);
    }

    /**
     * @param OperationFindCriteria $criteria
     * @param array $config
     * @return bool
     */
    private function isEntityClassMatched(OperationFindCriteria $criteria, array $config)
    {
        if (in_array('index', (array)$config['entities'])) {
            return true;
        }
        $applicable = $this->match(
            $criteria->getEntityClass(),
            $this->filterEntities((array)$config['entities']),
            (bool)$config['for_all_entities']
        );

        return $applicable && $this->isNotExcluded(
            $criteria->getEntityClass(),
            $this->filterEntities((array)$config['exclude_entities'])
        );
    }

    /**
     * @param OperationFindCriteria $criteria
     * @param array $config
     * @return bool
     */
    private function isRouteMatched(OperationFindCriteria $criteria, array $config)
    {
        $route = $criteria->getRoute();
        return $route && in_array($route, $config['routes'], true);
    }

    /**
     * @param OperationFindCriteria $criteria
     * @param array $config
     * @return bool
     */
    private function isDatagridMatched(OperationFindCriteria $criteria, array $config)
    {
        return $this->match(
            $criteria->getDatagrid(),
            (array)$config['datagrids'],
            (bool)$config['for_all_datagrids']
        );
    }

    /**
     * @param string $value
     * @param array $inclusion
     * @param bool $forAll
     * @return bool
     */
    protected function match($value, array $inclusion, $forAll)
    {
        if (!$value) {
            return false;
        }

        return $forAll || (!$forAll && in_array($value, $inclusion, true));
    }

    /**
     * @param string $value
     * @param array $exclusion
     * @return bool
     */
    protected function isNotExcluded($value, array $exclusion)
    {
        if (!$value) {
            return true;
        }

        return !in_array($value, $exclusion, true);
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
        if (!array_key_exists($entityName, $this->entityNames)) {
            $this->entityNames[$entityName] = null;

            try {
                $entityClass = $this->doctrineHelper->getEntityClass($entityName);

                if (class_exists($entityClass, true)) {
                    $this->entityNames[$entityName] = ltrim($entityClass, '\\');
                }
            } catch (ORMException $e) {
            }
        }

        return $this->entityNames[$entityName];
    }

    /**
     * @param OperationRegistryFilterInterface $operationRegistryFilter
     */
    public function addFilter(OperationRegistryFilterInterface $operationRegistryFilter)
    {
        $this->filters[] = $operationRegistryFilter;
    }

    /**
     * @param Operation[] $operations
     * @param OperationFindCriteria $findCriteria
     * @return Operation[]
     */
    private function filter($operations, OperationFindCriteria $findCriteria)
    {
        if (count($this->filters) === 0) {
            return $operations;
        }

        foreach ($this->filters as $filter) {
            $operations = $filter->filter($operations, $findCriteria);
        }

        return $operations;
    }
}
