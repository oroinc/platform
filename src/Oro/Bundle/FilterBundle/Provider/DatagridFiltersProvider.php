<?php

namespace Oro\Bundle\FilterBundle\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;
use Oro\Bundle\FilterBundle\Factory\FilterFactory;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration;

/**
 * Returns enabled and initialized filters for the specified datagrid config.
 */
class DatagridFiltersProvider implements DatagridFiltersProviderInterface
{
    private const LABEL_KEY = 'label';
    private const ORDER_KEY = 'order';

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

        $filtersConfig = $this->getSortedFilters($gridConfig);

        $filters = [];
        foreach ($filtersConfig as $filterName => $filterConfig) {
            $filters[$filterName] = $this->filterFactory->createFilter($filterName, $filterConfig);
        }

        return $filters;
    }

    private function getSortedFilters(DatagridConfiguration $gridConfig): array
    {
        $filtersConfig = $this->completeFilters($gridConfig);

        $weight  = 1;
        $defined = array_column($filtersConfig, self::ORDER_KEY);
        foreach ($filtersConfig as &$filterConfig) {
            $order = filter_var(
                $filterConfig[self::ORDER_KEY] ?? null,
                FILTER_VALIDATE_INT
            );
            if ($order === false) {
                while (\in_array($weight, $defined, true)) {
                    $weight++;
                }
                $defined[] = $weight;
                $order = $weight;
            }

            $filterConfig[self::ORDER_KEY] = $order;
        }
        unset($filterConfig);

        uasort($filtersConfig, static function (array $a, array $b) {
            return $a[self::ORDER_KEY] <=> $b[self::ORDER_KEY];
        });

        return $filtersConfig;
    }

    private function completeFilters(DatagridConfiguration $gridConfig): array
    {
        $filtersConfig = $gridConfig->offsetGetByPath(Configuration::COLUMNS_PATH, []);
        foreach ($filtersConfig as $filterName => &$filterConfig) {
            if (!\array_key_exists(self::LABEL_KEY, $filterConfig)) {
                $filterConfig[self::LABEL_KEY] = $this->getColumnOption($gridConfig, $filterName, self::LABEL_KEY);
                if (($filterConfig[FilterUtility::TRANSLATABLE_KEY] ?? true)
                    && $this->getColumnOption($gridConfig, $filterName, FilterUtility::TRANSLATABLE_KEY) === false
                ) {
                    $filterConfig[FilterUtility::TRANSLATABLE_KEY] = false;
                }
            }
            if (!\array_key_exists(self::ORDER_KEY, $filterConfig)) {
                $filterConfig[self::ORDER_KEY] = $this->getColumnOption($gridConfig, $filterName, self::ORDER_KEY);
            }
            if (!\array_key_exists(FilterUtility::DISABLED_KEY, $filterConfig)) {
                $filterConfig[FilterUtility::DISABLED_KEY] = $this->getColumnOption(
                    $gridConfig,
                    $filterName,
                    FilterUtility::DISABLED_KEY
                );
            }
            if (!empty($filterConfig[FilterUtility::DISABLED_KEY])) {
                unset($filtersConfig[$filterName]);
            }
        }
        unset($filterConfig);

        return $filtersConfig;
    }

    private function getColumnOption(DatagridConfiguration $gridConfig, string $columnName, string $optionName): mixed
    {
        return $gridConfig->offsetGetByPath(
            sprintf('[%s][%s][%s]', FormatterConfiguration::COLUMNS_KEY, $columnName, $optionName)
        );
    }
}
