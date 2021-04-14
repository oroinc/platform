<?php

namespace Oro\Bundle\FilterBundle\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\FilterBundle\Factory\FilterFactory;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration;

/**
 * Returns enabled and initialized filters for the specified datagrid config.
 */
class DatagridFiltersProvider implements DatagridFiltersProviderInterface
{
    private FilterFactory $filterFactory;

    private string $applicableDatasourceType;

    public function __construct(FilterFactory $filterFactory, string $applicableDatasourceType)
    {
        $this->filterFactory = $filterFactory;
        $this->applicableDatasourceType = $applicableDatasourceType;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatagridFilters(DatagridConfiguration $gridConfig): array
    {
        if ($gridConfig->getDatasourceType() !== $this->applicableDatasourceType) {
            return [];
        }

        $filtersConfig = $gridConfig->offsetGetByPath(Configuration::COLUMNS_PATH, []);

        foreach ($filtersConfig as $filterName => $filterConfig) {
            if (!empty($filterConfig[PropertyInterface::DISABLED_KEY])) {
                // Skips disabled filter.
                continue;
            }

            // If label is not set, tries to use corresponding column label.
            if (!isset($filterConfig['label'])) {
                $filterConfig['label'] = $gridConfig->offsetGetByPath(
                    sprintf('[%s][%s][label]', FormatterConfiguration::COLUMNS_KEY, $filterName)
                );
            }

            $filters[$filterName] = $this->filterFactory->createFilter($filterName, $filterConfig);
        }

        return $filters ?? [];
    }
}
