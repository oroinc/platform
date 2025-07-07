<?php

namespace Oro\Bundle\DataGridBundle\Datagrid\Common;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Component\Config\Common\ConfigObject;

/**
 * This class represents read & parsed datagrid configuration.
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DatagridConfiguration extends ConfigObject
{
    private const COLUMNS_PATH = '[columns]';
    private const FILTERS_PATH = '[filters][columns]';
    const COLUMN_PATH = '[columns][%s]';
    const SORTER_PATH = '[sorters][columns][%s]';
    const FILTER_PATH = '[filters][columns][%s]';
    const PROPERTY_PATH = '[properties][%s]';
    const DATASOURCE_PATH = '[source]';
    const DATASOURCE_TYPE_PATH = '[source][type]';
    const BASE_DATAGRID_CLASS_PATH  = '[options][base_datagrid_class]';
    const FROM_PATH = '[source][query][from]';
    const SELECT_PATH = '[source][query][select]';
    const ENTITY_HINT_PATH = '[options][entityHint]';
    const JS_MODULES_PATH = '[options][jsmodules]';
    const EMPTY_GRID_MESSAGE_PATH = '[options][noDataMessages][emptyGrid]';
    const EMPTY_FILTERED_GRID_MESSAGE_PATH = '[options][noDataMessages][emptyFilteredGrid]';

    const EXTENDED_ENTITY_NAME = 'extended_entity_name';

    // Use this option as workaround for http://www.doctrine-project.org/jira/browse/DDC-2794
    const DATASOURCE_SKIP_COUNT_WALKER_PATH = '[options][skip_count_walker]';

    /**
     * This option refers to ACL resource that will be checked before datagrid is loaded.
     */
    const ACL_RESOURCE_PATH = '[acl_resource]';

    /**
     * This option makes possible to skip apply of ACL adjustment to source query of datagrid.
     */
    const DATASOURCE_SKIP_ACL_APPLY_PATH = '[source][skip_acl_apply]';

    /**
     * This option allow to pass additional context to
     * {@see \Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder} to make additional checks
     * during getting the ACL condition data.
     */
    const ACL_CONDITION_DATA_BUILDER_CONTEXT = '[source][acl_condition_data_builder_context]';

    /**
     * This option sets what ACL permission will be applied to datasource if value is DATASOURCE_SKIP_ACL_APPLY_PATH
     * is set to false. Default value of this setting is VIEW.
     */
    const DATASOURCE_ACL_APPLY_PERMISSION_PATH = '[source][acl_apply_permission]';

    /**
     * A datagrid parameters to datasource parameters binding.
     */
    const DATASOURCE_BIND_PARAMETERS_PATH = '[source][bind_parameters]';

    /** @var object|null */
    private $query;

    /**
     * Gets an instance of OrmQueryConfiguration that can be used to configure ORM query.
     *
     * @return OrmQueryConfiguration
     */
    public function getOrmQuery()
    {
        if (null === $this->query) {
            $datasourceType = $this->getDatasourceType();
            if (!$datasourceType || OrmDatasource::TYPE === $datasourceType) {
                $this->query = new OrmQueryConfiguration($this);
            }
        }
        if (!$this->query instanceof OrmQueryConfiguration) {
            throw new LogicException(
                sprintf(
                    'The expected data grid source type is "%s". Actual source type is "%s".',
                    OrmDatasource::TYPE,
                    $this->getDatasourceType()
                )
            );
        }

        return $this->query;
    }

    /**
     * Indicates whether the grid is based on ORM query.
     *
     * @return bool
     */
    public function isOrmDatasource()
    {
        return OrmDatasource::TYPE === $this->getDatasourceType();
    }

    /**
     * @return string
     */
    public function getDatasourceType()
    {
        return $this->offsetGetByPath(self::DATASOURCE_TYPE_PATH);
    }

    /**
     * @param string $type
     *
     * @return self
     */
    public function setDatasourceType($type)
    {
        $this->offsetSetByPath(self::DATASOURCE_TYPE_PATH, $type);

        return $this;
    }

    /**
     * Gets the class name of extended entity the query is related with.
     *
     * @return string|null
     */
    public function getExtendedEntityClassName()
    {
        return $this->offsetGetOr(self::EXTENDED_ENTITY_NAME);
    }

    /**
     * Sets or unsets the class name of extended entity the query is related with.
     *
     * @param string|null $className
     *
     * @return self
     */
    public function setExtendedEntityClassName($className)
    {
        if ($className) {
            $this->offsetSet(self::EXTENDED_ENTITY_NAME, $className);
        } else {
            $this->offsetUnset(self::EXTENDED_ENTITY_NAME);
        }

        return $this;
    }

    /**
     * Get value of "acl_resource" option from datagrid configuration.
     */
    public function getAclResource(): ?string
    {
        return $this->offsetGetByPath(self::ACL_RESOURCE_PATH);
    }

    /**
     * Check if ACL apply to source query of datagrid should be skipped
     */
    public function isDatasourceSkipAclApply(): bool
    {
        return (bool)$this->offsetGetByPath(self::DATASOURCE_SKIP_ACL_APPLY_PATH);
    }

    /**
     * Gets ACL permission which should be applied to datasource if isDatasourceSkipAclApply() returns false.
     *
     * @return string
     */
    public function getDatasourceAclApplyPermission()
    {
        return $this->offsetGetByPath(self::DATASOURCE_ACL_APPLY_PERMISSION_PATH, 'VIEW');
    }

    /**
     * Sets ACL permission which should be applied to datasource if isDatasourceSkipAclApply() returns false.
     *
     * @param string $value
     * @return string
     */
    public function setDatasourceAclApplyPermission($value)
    {
        return $this->offsetSetByPath(self::DATASOURCE_ACL_APPLY_PERMISSION_PATH, $value);
    }

    /**
     * @param string $name
     * @param string $label
     *
     * @return self
     */
    public function updateLabel($name, $label)
    {
        if (empty($name)) {
            throw new \BadMethodCallException('DatagridConfiguration::updateLabel: name should not be empty');
        }

        $this->offsetSetByPath(sprintf(self::COLUMN_PATH.'[label]', $name), $label);

        return $this;
    }

    /**
     * @param string      $name       column name
     * @param array       $definition definition array as in datagrids.yml
     * @param null|string $select     select part for the column
     * @param array       $sorter     sorter definition
     * @param array       $filter     filter definition
     *
     * @return self
     */
    public function addColumn($name, array $definition, $select = null, array $sorter = [], array $filter = [])
    {
        if (empty($name)) {
            throw new \BadMethodCallException('DatagridConfiguration::addColumn: name should not be empty');
        }

        $this->offsetSetByPath(
            sprintf(self::COLUMN_PATH, $name),
            $definition
        );

        if (!is_null($select)) {
            $this->getOrmQuery()->addSelect($select);
        }

        if (!empty($sorter)) {
            $this->addSorter($name, $sorter);
        }

        if (!empty($filter)) {
            $this->addFilter($name, $filter);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param array  $definition
     *
     * @return self
     */
    public function addFilter($name, array $definition)
    {
        $this->offsetSetByPath(
            sprintf(self::FILTER_PATH, $name),
            $definition
        );

        return $this;
    }

    /**
     * @param string $name
     * @param array  $definition
     *
     * @return self
     */
    public function addSorter($name, array $definition)
    {
        $this->offsetSetByPath(
            sprintf(self::SORTER_PATH, $name),
            $definition
        );

        return $this;
    }

    /**
     * @param string $name
     * @param array  $definition
     *
     * @return self
     */
    public function addProperty($name, array $definition)
    {
        $this->offsetSetByPath(
            sprintf(self::PROPERTY_PATH, $name),
            $definition
        );

        return $this;
    }

    /**
     * Remove column definition
     * should remove sorters as well and optionally filters
     *
     * @param string $name         column name from grid definition
     * @param bool   $removeFilter whether remove filter or not, true by default
     *
     * @return self
     */
    public function removeColumn($name, $removeFilter = true)
    {
        $this->offsetUnsetByPath(
            sprintf(self::COLUMN_PATH, $name)
        );

        $this->removeSorter($name);
        if ($removeFilter) {
            $this->removeFilter($name);
        }

        return $this;
    }

    /**
     * @param string $name column name
     */
    public function removeSorter($name)
    {
        $this->offsetUnsetByPath(
            sprintf(self::SORTER_PATH, $name)
        );
    }

    /**
     * Remove filter definition
     *
     * @param string $name column name
     *
     * @return self
     */
    public function removeFilter($name)
    {
        $this->offsetUnsetByPath(
            sprintf(self::FILTER_PATH, $name)
        );

        return $this;
    }

    /**
     * @param string $columnName column name
     * @param string $dataName   property path of the field, e.g. entity.enum_field
     * @param string $enumCode   enum code
     * @param bool   $isMultiple allow to filter by several values
     *
     * @return self
     */
    public function addEnumFilter($columnName, $dataName, $enumCode, $isMultiple = false)
    {
        $this->addFilter(
            $columnName,
            [
                'type' => 'entity',
                'data_name' => $dataName,
                'options' => [
                    'field_options' => [
                        'class' => EnumOption::class,
                        'enum_code' => $enumCode,
                        'choice_label' => 'name',
                        'query_builder' => function (EntityRepository $entityRepository) {
                            return $entityRepository->createQueryBuilder('c')
                                ->orderBy('c.name', 'ASC');
                        },
                        'multiple' => $isMultiple,
                    ],
                ],
            ]
        );

        return $this;
    }

    /**
     * @param string      $name
     * @param string      $label
     * @param string      $templatePath
     * @param null|string $select select part for the column
     * @param array       $sorter sorter definition
     * @param array       $filter filter definitio
     *
     * @return self
     */
    public function addTwigColumn($name, $label, $templatePath, $select = null, array $sorter = [], array $filter = [])
    {
        $this->addColumn(
            $name,
            [
                'label'         => $label,
                'type'          => 'twig',
                'frontend_type' => 'html',
                'template'      => $templatePath,
            ],
            $select,
            $sorter,
            $filter
        );

        return $this;
    }

    /**
     * @param string $name
     * @param array  $options
     *
     * @return self
     */
    public function addMassAction($name, array $options)
    {
        $this->offsetSetByPath(
            sprintf('[mass_actions][%s]', $name),
            $options
        );

        return $this;
    }

    /**
     * @param string $datagridName
     * @return bool
     */
    public function isDatagridExtendedFrom($datagridName)
    {
        $parentGrids = $this->offsetGetOr(SystemAwareResolver::KEY_EXTENDED_FROM, []);
        foreach ($parentGrids as $parentGridName) {
            if ($parentGridName === $datagridName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Changes a priority of a column by moving it before a specified target column.
     */
    public function moveColumnBefore(string $name, string $targetName): void
    {
        $this->moveBefore($name, $targetName, self::COLUMNS_PATH);
    }

    /**
     * Changes a priority of a column by moving it after a specified target column.
     */
    public function moveColumnAfter(string $name, string $targetName): void
    {
        $this->moveAfter($name, $targetName, self::COLUMNS_PATH);
    }

    /**
     * Changes a priority of a filter by moving it before a specified target filter.
     */
    public function moveFilterBefore(string $name, string $targetName): void
    {
        $this->moveBefore($name, $targetName, self::FILTERS_PATH);
    }

    /**
     * Changes a priority of a filter by moving it after a specified target filter.
     */
    public function moveFilterAfter(string $name, string $targetName): void
    {
        $this->moveAfter($name, $targetName, self::FILTERS_PATH);
    }

    private function moveBefore(string $name, string $targetName, string $sectionPath): void
    {
        $section = $this->offsetGetByPath($sectionPath);
        if (\array_key_exists($name, $section) && \array_key_exists($targetName, $section)) {
            $updatedSection = [];
            foreach ($section as $key => $value) {
                if ($key === $name) {
                    continue;
                }
                if ($key === $targetName) {
                    $updatedSection[$name] = $section[$name];
                }
                $updatedSection[$key] = $value;
            }
            $this->offsetSetByPath($sectionPath, $updatedSection);
        }
    }

    private function moveAfter(string $name, string $targetName, string $sectionPath): void
    {
        $section = $this->offsetGetByPath($sectionPath);
        if (\array_key_exists($name, $section) && \array_key_exists($targetName, $section)) {
            $updatedSection = [];
            foreach ($section as $key => $value) {
                if ($key === $name) {
                    continue;
                }
                $updatedSection[$key] = $value;
                if ($key === $targetName) {
                    $updatedSection[$name] = $section[$name];
                }
            }
            $this->offsetSetByPath($sectionPath, $updatedSection);
        }
    }
}
