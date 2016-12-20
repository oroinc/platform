<?php

namespace Oro\Bundle\CurrencyBundle\Datagrid\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\EventListener\AbstractDatagridListener;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\CurrencyBundle\Datagrid\InlineEditing\InlineEditColumnOptions\MultiCurrencyGuesser as Guesser;

class ColumnConfigListener extends AbstractDatagridListener
{
    /** @var array  */
    protected $multiCurrencyConfigValueFormats = [
        'original_field' => '%s',
        'value_field' => '%sValue',
        'currency_field' => '%sCurrency'
    ];

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();

        // datasource type other than ORM is not supported yet
        if ($config->getDatasourceType() !== OrmDatasource::TYPE) {
            return;
        }

        // get root entity
        list($rootEntity, $rootEntityAlias) = $this->getRootEntityNameAndAlias($config);

        if (!$rootEntity || !$rootEntityAlias) {
            return;
        }

        $columns = $config->offsetGetByPath('[columns]', []);
        $newColumnsSet = [];

        foreach ($columns as $columnName => $columnConfig) {
            $newColumnsSet[$columnName] = $columnConfig;
            if (isset($columnConfig[PropertyInterface::FRONTEND_TYPE_KEY]) &&
                $columnConfig[PropertyInterface::FRONTEND_TYPE_KEY] == Guesser::MULTI_CURRENCY_TYPE) {
                $multiCurrencyConfigOptions = $this->guessMultiCurrencyConfigOptions(
                    $columnName,
                    $columnConfig
                );

                $newColumnsSet[$columnName][Guesser::MULTI_CURRENCY_CONFIG] = $multiCurrencyConfigOptions;
                $newColumnsSet[$columnName]['params'] = [
                    'value' => $multiCurrencyConfigOptions['original_field'],
                    'currency' => $multiCurrencyConfigOptions['currency_field']
                ];
            }
        }

        $config->offsetSetByPath('[columns]', $newColumnsSet);
    }

    /**
     * @param string $columnName
     * @param array  $column
     *
     * @return array
     */
    protected function guessMultiCurrencyConfigOptions($columnName, array $column)
    {
        $multiCurrencyConfig = isset($column[Guesser::MULTI_CURRENCY_CONFIG])
            ? $column[Guesser::MULTI_CURRENCY_CONFIG]
            : [];

        foreach ($this->multiCurrencyConfigValueFormats as $configKey => $configValueFormat) {
            if (empty($multiCurrencyConfig[$configKey])) {
                $multiCurrencyConfig[$configKey] = sprintf(
                    $configValueFormat,
                    $columnName
                );
            }
        }

        return $multiCurrencyConfig;
    }
}
