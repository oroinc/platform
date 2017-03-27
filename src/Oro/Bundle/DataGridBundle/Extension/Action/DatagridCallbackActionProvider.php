<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;

/**
 * Applies property config for callback type of action configuration
 */
class DatagridCallbackActionProvider implements DatagridActionProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function hasActions(DatagridConfiguration $configuration)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function applyActions(DatagridConfiguration $configuration)
    {
        $actionConfiguration = $configuration->offsetGetOr(ActionExtension::ACTION_CONFIGURATION_KEY);

        if ($actionConfiguration && is_callable($actionConfiguration)) {
            $callable = function (ResultRecordInterface $record) use ($actionConfiguration, $configuration) {
                $result = $actionConfiguration($record, $configuration->offsetGetOr(ActionExtension::ACTION_KEY, []));

                return is_array($result) ? $result : [];
            };

            $propertyConfig = [
                'type' => 'callback',
                'callable' => $callable,
                PropertyInterface::FRONTEND_TYPE_KEY => 'row_array'
            ];
            $configuration->offsetAddToArrayByPath(
                sprintf('[%s][%s]', Configuration::PROPERTIES_KEY, ActionExtension::METADATA_ACTION_CONFIGURATION_KEY),
                $propertyConfig
            );
        }
    }
}
