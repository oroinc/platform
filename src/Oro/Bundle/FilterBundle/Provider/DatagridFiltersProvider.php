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
    public const ORDER_FIELD_NAME  = 'order';
    public const ORDER_FIELD_LABEL = 'label';

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

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @param DatagridConfiguration $gridConfig
     * @return array
     */
    private function getSortedFilters(DatagridConfiguration $gridConfig): array
    {
        $filtersConfig = $gridConfig->offsetGetByPath(Configuration::COLUMNS_PATH, []);
        foreach ($filtersConfig as $filterName => &$filterConfig) {
            foreach ([self::ORDER_FIELD_LABEL, self::ORDER_FIELD_NAME, FilterUtility::DISABLED_KEY] as $field) {
                if (!array_key_exists($field, $filterConfig)) {
                    $filterConfig[$field] = $gridConfig->offsetGetByPath(
                        sprintf('[%s][%s][%s]', FormatterConfiguration::COLUMNS_KEY, $filterName, $field)
                    );
                }
            }

            // BC layer start
            if (isset($filterConfig['enabled'])) {
                if (!$filterConfig['enabled']) {
                    $filterConfig[FilterUtility::RENDERABLE_KEY] = false;
                }
                unset($filterConfig['enabled']);
            }
            // BC layer end

            if (!empty($filterConfig[FilterUtility::DISABLED_KEY])) {
                unset($filtersConfig[$filterName]);
            }
        }
        unset($filterConfig);

        $weight  = 1;
        $defined = array_column($filtersConfig, self::ORDER_FIELD_NAME);
        foreach ($filtersConfig as &$filterConfig) {
            $order = filter_var(
                $filterConfig[self::ORDER_FIELD_NAME] ?? null,
                FILTER_VALIDATE_INT
            );
            if ($order === false) {
                while (\in_array($weight, $defined, true)) {
                    $weight++;
                }
                $defined[] = $weight;
                $order = $weight;
            }

            $filterConfig[self::ORDER_FIELD_NAME] = $order;
        }
        unset($filterConfig);

        uasort($filtersConfig, static function (array $a, array $b) {
            if ($a[self::ORDER_FIELD_NAME] === $b[self::ORDER_FIELD_NAME]) {
                return 0;
            }
            return ($a[self::ORDER_FIELD_NAME] < $b[self::ORDER_FIELD_NAME]) ? -1 : 1;
        });

        return $filtersConfig;
    }
}
