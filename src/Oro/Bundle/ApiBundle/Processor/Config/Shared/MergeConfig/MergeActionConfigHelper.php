<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig;

use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfigLoader;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Provides a method to merge entity configuration with configuration of an action.
 */
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
        if ($withStatusCodes && !empty($actionConfig[ConfigUtil::STATUS_CODES])) {
            $config = $this->mergeStatusCodes(
                $config,
                $this->loadStatusCodes($actionConfig[ConfigUtil::STATUS_CODES])
            );
        }
        unset($actionConfig[ConfigUtil::STATUS_CODES]);

        unset($actionConfig[ConfigUtil::EXCLUDE]);
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

    /**
     * @param array $config
     * @param array $actionConfig
     *
     * @return array
     */
    protected function mergeActionConfigValues(array $config, array $actionConfig)
    {
        // merge form options and event subscribers only if form type is not changed
        if (empty($actionConfig[ConfigUtil::FORM_TYPE])) {
            $mergeKeys = [ConfigUtil::FORM_OPTIONS, ConfigUtil::FORM_EVENT_SUBSCRIBER];
            foreach ($mergeKeys as $mergeKey) {
                if (\array_key_exists($mergeKey, $actionConfig) && \array_key_exists($mergeKey, $config)) {
                    $actionConfig[$mergeKey] = \array_merge($config[$mergeKey], $actionConfig[$mergeKey]);
                    unset($config[$mergeKey]);
                }
            }
        }

        return \array_merge($config, $actionConfig);
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
                    $fields[$key] = \array_merge($fields[$key], $value);
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
