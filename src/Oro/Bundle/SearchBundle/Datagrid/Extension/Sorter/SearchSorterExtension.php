<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Extension\Sorter\AbstractSorterExtension;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Exception\InvalidConfigurationException;

class SearchSorterExtension extends AbstractSorterExtension
{
    // data type mapping from configuration type to search engine type
    /** @var array */
    protected static $typeMapping = [
        'string'   => 'text',
        'integer'  => 'integer',
        'decimal'  => 'decimal',
        'datetime' => 'datetime',
    ];

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            SearchDatasource::TYPE === $config->getDatasourceType()
            && parent::isApplicable($config);
    }

    /**
     * {@inheritdoc}
     */
    protected function addSorterToDatasource(array $sorter, $direction, DatasourceInterface $datasource)
    {
        $sortKey = $sorter['data_name'];

        /* @var $datasource SearchDatasource */
        if (array_key_exists(PropertyInterface::TYPE_KEY, $sorter)) {
            // pass type if specified
            $type = $this->mapType($sorter[PropertyInterface::TYPE_KEY]);
            $datasource->getSearchQuery()->setOrderBy($sortKey, $direction, $type);
        } else {
            // otherwise use default type
            $datasource->getSearchQuery()->setOrderBy($sortKey, $direction);
        }
    }

    /**
     * Returns corresponding search data type for given configuration data type
     *
     * @param $configType
     * @return string
     * @throws InvalidConfigurationException On unknoewn config type
     */
    protected function mapType($configType)
    {
        if (array_key_exists($configType, static::$typeMapping)) {
            return static::$typeMapping[$configType];
        } else {
            throw new InvalidConfigurationException(sprintf(
                'Unknown data type \'%s\', possible values: \'%s\'',
                $configType,
                implode('\', \'', static::$typeMapping)
            ));
        }
    }
}
