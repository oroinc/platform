<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig;

use Oro\Bundle\ApiBundle\Config\ActionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfigLoader;

class MergeActionConfigHelper
{
    /**
     * @param array $config
     * @param array $actionConfig
     * @param bool  $withStatusCodes
     *
     * @return array
     */
    public function mergeActionConfig(array $config, array $actionConfig, $withStatusCodes)
    {
        if ($withStatusCodes && !empty($actionConfig[ActionConfig::STATUS_CODES])) {
            $config = $this->mergeStatusCodes(
                $config,
                $this->loadStatusCodes($actionConfig[ActionConfig::STATUS_CODES])
            );
        }
        unset($actionConfig[ActionConfig::STATUS_CODES]);

        unset($actionConfig[ActionConfig::EXCLUDE]);
        $actionFields = null;
        if (array_key_exists(ActionConfig::FIELDS, $actionConfig)) {
            $actionFields = $actionConfig[ActionConfig::FIELDS];
            unset($actionConfig[ActionConfig::FIELDS]);
        }
        if (!empty($actionConfig)) {
            $config = $this->mergeActionConfigValues($config, $actionConfig);
        }
        if (!empty($actionFields)) {
            $config[EntityDefinitionConfig::FIELDS] = !empty($config[EntityDefinitionConfig::FIELDS])
                ? $this->mergeActionFields($config[EntityDefinitionConfig::FIELDS], $actionFields)
                : $actionFields;
        }

        return $config;
    }

    /**
     * @param array $config
     * @param array $actionConfig
     *
     * @return array
     */
    protected function mergeActionConfigValues(array $config, array $actionConfig)
    {
        // merge form options and event subscribers only if form type is not changed
        if (empty($actionConfig[EntityDefinitionConfig::FORM_TYPE])) {
            $mergeKeys = [
                EntityDefinitionConfig::FORM_OPTIONS,
                EntityDefinitionConfig::FORM_EVENT_SUBSCRIBER
            ];
            foreach ($mergeKeys as $mergeKey) {
                if (array_key_exists($mergeKey, $actionConfig) && array_key_exists($mergeKey, $config)) {
                    $actionConfig[$mergeKey] = array_merge($config[$mergeKey], $actionConfig[$mergeKey]);
                    unset($config[$mergeKey]);
                }
            }
        }

        return array_merge($config, $actionConfig);
    }

    /**
     * @param array $fields
     * @param array $actionFields
     *
     * @return array
     */
    protected function mergeActionFields(array $fields, array $actionFields)
    {
        foreach ($actionFields as $key => $value) {
            if (!empty($fields[$key])) {
                if (!empty($value)) {
                    $fields[$key] = array_merge($fields[$key], $value);
                }
            } else {
                $fields[$key] = $value;
            }
        }

        return $fields;
    }

    /**
     * @param array $statusCodesConfig
     *
     * @return StatusCodesConfig
     */
    protected function loadStatusCodes(array $statusCodesConfig)
    {
        $statusCodesLoader = new StatusCodesConfigLoader();

        return $statusCodesLoader->load($statusCodesConfig);
    }

    /**
     * @param array             $config
     * @param StatusCodesConfig $statusCodes
     *
     * @return array
     */
    protected function mergeStatusCodes(array $config, StatusCodesConfig $statusCodes)
    {
        if (!array_key_exists(ActionConfig::STATUS_CODES, $config)) {
            $config[ActionConfig::STATUS_CODES] = $statusCodes;
        } else {
            /** @var StatusCodesConfig $existingStatusCodes */
            $existingStatusCodes = $config[ActionConfig::STATUS_CODES];
            $codes = $statusCodes->getCodes();
            foreach ($codes as $code => $statusCode) {
                $existingStatusCodes->addCode($code, $statusCode);
            }
        }

        return $config;
    }
}
