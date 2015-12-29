<?php

namespace Oro\Bundle\DataGridBundle\Datagrid\Common;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DataGridBundle\Common\Object;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class DatagridConfiguration extends Object
{
    const COLUMN_PATH = '[columns][%s]';
    const SORTER_PATH = '[sorters][columns][%s]';
    const FILTER_PATH = '[filters][columns][%s]';

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
