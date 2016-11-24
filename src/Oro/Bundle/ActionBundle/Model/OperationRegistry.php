<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\ORM\ORMException;

use Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface;
use Oro\Bundle\ActionBundle\Helper\ArraySubstitution;
use Oro\Bundle\ActionBundle\Model\Assembler\OperationAssembler;
use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class OperationRegistry
{
    const DEFAULT_GROUP = '';

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
     * @param string|null $entityClass
     * @param string|null $route
     * @param string|null $datagrid
     * @param string|array|null $group
     * @return Operation[]
     */
    public function find($entityClass, $route, $datagrid, $group = null)
    {
        $this->loadConfiguration();

        $configurations = $this->filterByGroup($group);

        /** @var Operation[] $operations */
        $operations = [];
        $replacements = [];

        foreach ($configurations as $name => $config) {
            if (!$this->isApplicable($config, $entityClass, $route, $datagrid)) {
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

        return $operations;
    }

    /**
     * @param string $name
     * @return null|Operation
     */
    public function findByName($name)
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
     * @param string|array|null $group
     * @return array
     */
    protected function filterByGroup($group = null)
    {
        $expected = $this->normalizeGroups($group);

        return array_filter($this->configuration, function (array $operation) use ($expected) {
            $groups = (array)$operation['groups'] ?: [static::DEFAULT_GROUP];

            return 0 !== count(array_intersect($expected, $groups));
        });
    }

    /**
     * @param array $config
     * @param string|null $entityClass
     * @param string|null $route
     * @param string|null $datagrid
     * @return bool
     */
    protected function isApplicable(array $config, $entityClass, $route, $datagrid)
    {
        if (!(bool)$config['enabled']) {
            return false;
        }

        if (!$this->applicationProvider->isApplicationsValid((array)$config['applications'])) {
            return false;
        }

        return $this->isEntityClassMatched($entityClass, $config) ||
            $this->isRouteMatched($route, $config) ||
            $this->isDatagridMatched($datagrid, $config);
    }

    /**
     * @param string $className
     * @param array $config
     * @return bool
     */
    private function isEntityClassMatched($className, array $config)
    {
        return $this->match(
            $className,
            $this->filterEntities((array)$config['entities']),
            $this->filterEntities((array)$config['exclude_entities']),
            (bool)$config['for_all_entities']
        );
    }

    /**
     * @param string $route
     * @param array $config
     * @return bool
     */
    private function isRouteMatched($route, array $config)
    {
        return $route && in_array($route, $config['routes'], true);
    }

    /**
     * @param string $datagrid
     * @param array $config
     * @return bool
     */
    private function isDatagridMatched($datagrid, array $config)
    {
        return $this->match(
            $datagrid,
            (array)$config['datagrids'],
            (array)$config['exclude_datagrids'],
            (bool)$config['for_all_datagrids']
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
     * @return array
     */
    protected function normalizeGroups($group)
    {
        $groups = (array)$group;

        if (!$groups) {
            $groups = [static::DEFAULT_GROUP];
        }

        return array_map(
            function ($group) {
                return (string)$group;
            },
            $groups
        );
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
}
