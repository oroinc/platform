<?php

namespace Oro\Bundle\DataGridBundle\Datagrid\Common;

use Doctrine\ORM\EntityRepository;

use Oro\Component\Config\Common\ConfigObject;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\DataGridBundle\Datagrid\Builder;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DatagridConfiguration extends ConfigObject
{
    const COLUMN_PATH = '[columns][%s]';
    const SORTER_PATH = '[sorters][columns][%s]';
    const FILTER_PATH = '[filters][columns][%s]';
    const DATASOURCE_PATH = '[source]';
    const DATASOURCE_TYPE_PATH = '[source][type]';
    const BASE_DATAGRID_CLASS_PATH  = '[options][base_datagrid_class]';

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
     * This option sets what ACL permission will be applied to datasource if value is DATASOURCE_SKIP_ACL_APPLY_PATH
     * is set to false. Default value of this setting is VIEW.
     */
    const DATASOURCE_ACL_APPLY_PERMISSION_PATH = '[source][acl_apply_permission]';

    /**
     * @return string
     */
    public function getDatasourceType()
    {
        return $this->offsetGetByPath(self::DATASOURCE_TYPE_PATH);
    }

    /**
     * Get value of "acl_resource" option from datagrid configuration.
     *
     * @return string|null
     */
    public function getAclResource()
    {
        if ($this->offsetExistByPath(self::ACL_RESOURCE_PATH)) {
            $result = $this->offsetGetByPath(self::ACL_RESOURCE_PATH);
        } else {
            // Support backward compatibility until 1.11 to get this option from deprecated path.
            $result = $this->offsetGetByPath(Builder::DATASOURCE_ACL_PATH, false);
        }

        return $result;
    }

    /**
     * Check if ACL apply to source query of datagrid should be skipped
     *
     * @return bool
     */
    public function isDatasourceSkipAclApply()
    {
        if ($this->offsetExistByPath(self::DATASOURCE_SKIP_ACL_APPLY_PATH)) {
            $result = $this->offsetGetByPath(self::DATASOURCE_SKIP_ACL_APPLY_PATH);
        } else {
            // Support backward compatibility until 1.11 to get this option from deprecated path.
            $result = $this->offsetGetByPath(Builder::DATASOURCE_SKIP_ACL_CHECK, false);
        }

        return (bool)$result;
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
     * @return DatagridConfiguration
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
     * @param array       $definition definition array as in datagrid.yml
     * @param null|string $select     select part for the column
     * @param array       $sorter     sorter definition
     * @param array       $filter     filter definition
     *
     * @return DatagridConfiguration
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
            $this->addSelect($select);
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
     * @param string $select
     *
     * @return DatagridConfiguration
     */
    public function addSelect($select)
    {
        if (empty($select)) {
            throw new \BadMethodCallException('DatagridConfiguration::addSelect: select should not be empty');
        }

        $this->offsetAddToArrayByPath(
            '[source][query][select]',
            [$select]
        );

        return $this;
    }

    /**
     * @param string $type
     * @param array  $definition
     *
     * @return DatagridConfiguration
     */
    public function joinTable($type, array $definition)
    {
        $this
            ->offsetAddToArrayByPath(
                sprintf('[source][query][join][%s]', $type),
                [$definition]
            );

        return $this;
    }

    /**
     * @param string $name
     * @param array  $definition
     *
     * @return DatagridConfiguration
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
     * @return DatagridConfiguration
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
     * Remove column definition
     * should remove sorters as well and optionally filters
     *
     * @param string $name         column name from grid definition
     * @param bool   $removeFilter whether remove filter or not, true by default
     *
     * @return DatagridConfiguration
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
     * @return DatagridConfiguration
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
     * @return DatagridConfiguration
     */
    public function addEnumFilter($columnName, $dataName, $enumCode, $isMultiple = false)
    {
        $this->addFilter(
            $columnName,
            [
                'type'      => 'entity',
                'data_name' => $dataName,
                'options'   => [
                    'field_options' => [
                        'class'         => ExtendHelper::buildEnumValueClassName($enumCode),
                        'property'      => 'name',
                        'query_builder' => function (EntityRepository $entityRepository) {
                            return $entityRepository->createQueryBuilder('c')
                                ->orderBy('c.name', 'ASC');
                        },
                        'multiple'      => $isMultiple,
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
     * @return DatagridConfiguration
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
     * @return DatagridConfiguration
     */
    public function addMassAction($name, array $options)
    {
        $this->offsetSetByPath(
            sprintf('[mass_actions][%s]', $name),
            $options
        );

        return $this;
    }
}
