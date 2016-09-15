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

    /** @var ArraySubstitution */
    protected $substitution;

    /** @var array */
    protected $configuration;

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
        $this->loadConfiguration();

        $configurations = $this->filterByGroup($group);

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

        if (!$this->applicationsHelper->isApplicationsValid((array)$config['applications'])) {
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
            (array)$config['entities'],
            (array)$config['exclude_entities'],
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
}
