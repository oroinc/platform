<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\MergeConfig;

use Oro\Bundle\ApiBundle\Config\Loader\StatusCodesConfigLoader;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Provides a method to merge entity configuration with configuration of an action.
 */
class MergeActionConfigHelper
{
    public function mergeActionConfig(array $config, array $actionConfig, bool $withStatusCodes): array
    {
        if ($withStatusCodes && !empty($actionConfig[ConfigUtil::STATUS_CODES])) {
            $config = $this->mergeStatusCodes(
                $config,
                $this->loadStatusCodes($actionConfig[ConfigUtil::STATUS_CODES])
            );
        }
        unset($actionConfig[ConfigUtil::STATUS_CODES], $actionConfig[ConfigUtil::EXCLUDE]);

        $actionFields = null;
        if (\array_key_exists(ConfigUtil::FIELDS, $actionConfig)) {
            $actionFields = $actionConfig[ConfigUtil::FIELDS];
            unset($actionConfig[ConfigUtil::FIELDS]);
        }
        if (!empty($actionConfig)) {
            $config = $this->mergeActionConfigValues($config, $actionConfig);
        }
        if (!empty($actionFields)) {
            $config[ConfigUtil::FIELDS] = !empty($config[ConfigUtil::FIELDS])
                ? $this->mergeActionFields($config[ConfigUtil::FIELDS], $actionFields)
                : $actionFields;
        }

        return $config;
    }

    protected function mergeActionConfigValues(array $config, array $actionConfig): array
    {
        // merge form options and event subscribers only if form type is not changed
        if (empty($actionConfig[ConfigUtil::FORM_TYPE])) {
            $mergeKeys = [ConfigUtil::FORM_OPTIONS, ConfigUtil::FORM_EVENT_SUBSCRIBER];
            foreach ($mergeKeys as $mergeKey) {
                if (\array_key_exists($mergeKey, $actionConfig) && \array_key_exists($mergeKey, $config)) {
                    $actionConfig[$mergeKey] = array_merge($config[$mergeKey], $actionConfig[$mergeKey]);
                    unset($config[$mergeKey]);
                }
            }
        }

        return array_merge($config, $actionConfig);
    }

    protected function mergeActionFields(array $fields, array $actionFields): array
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

    protected function loadStatusCodes(array $statusCodesConfig): StatusCodesConfig
    {
        return (new StatusCodesConfigLoader())->load($statusCodesConfig);
    }

    protected function mergeStatusCodes(array $config, StatusCodesConfig $statusCodes): array
    {
        if (!\array_key_exists(ConfigUtil::STATUS_CODES, $config)) {
            $config[ConfigUtil::STATUS_CODES] = $statusCodes;
        } else {
            /** @var StatusCodesConfig $existingStatusCodes */
            $existingStatusCodes = $config[ConfigUtil::STATUS_CODES];
            $codes = $statusCodes->getCodes();
            foreach ($codes as $code => $statusCode) {
                $existingStatusCodes->addCode($code, $statusCode);
            }
        }

        return $config;
    }
}
