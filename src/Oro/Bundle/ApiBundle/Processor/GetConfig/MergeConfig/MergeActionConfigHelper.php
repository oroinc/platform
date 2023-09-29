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
    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function mergeActionConfig(array $config, array $actionConfig, bool $withStatusCodes): array
    {
        if ($withStatusCodes && !empty($actionConfig[ConfigUtil::STATUS_CODES])) {
            $config = $this->mergeStatusCodes(
                $config,
                $this->loadStatusCodes($actionConfig[ConfigUtil::STATUS_CODES])
            );
        }
        if (!empty($actionConfig[ConfigUtil::UPSERT])) {
            $config = $this->mergeUpsertConfig($config, $actionConfig[ConfigUtil::UPSERT]);
        }
        unset(
            $actionConfig[ConfigUtil::STATUS_CODES],
            $actionConfig[ConfigUtil::UPSERT],
            $actionConfig[ConfigUtil::EXCLUDE]
        );

        $actionDisabledMetaProperties = null;
        if (\array_key_exists(ConfigUtil::DISABLED_META_PROPERTIES, $actionConfig)) {
            $actionDisabledMetaProperties = $actionConfig[ConfigUtil::DISABLED_META_PROPERTIES];
            unset($actionConfig[ConfigUtil::DISABLED_META_PROPERTIES]);
        }
        $actionFields = null;
        if (\array_key_exists(ConfigUtil::FIELDS, $actionConfig)) {
            $actionFields = $actionConfig[ConfigUtil::FIELDS];
            unset($actionConfig[ConfigUtil::FIELDS]);
        }
        if (!empty($actionConfig)) {
            $config = $this->mergeActionConfigValues($config, $actionConfig);
        }
        if (!empty($actionDisabledMetaProperties)) {
            $config[ConfigUtil::DISABLED_META_PROPERTIES] = !empty($config[ConfigUtil::DISABLED_META_PROPERTIES])
                ? $this->mergeActionDisabledMetaProperties(
                    $config[ConfigUtil::DISABLED_META_PROPERTIES],
                    $actionDisabledMetaProperties
                )
                : $actionDisabledMetaProperties;
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

    protected function mergeActionDisabledMetaProperties(
        array $disabledMetaProperties,
        array $actionDisabledMetaProperties
    ): array {
        return array_values(array_unique(array_merge($disabledMetaProperties, $actionDisabledMetaProperties)));
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

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function mergeUpsertConfig(array $config, array $actionUpsertConfig): array
    {
        if (!isset($config[ConfigUtil::UPSERT])) {
            $config[ConfigUtil::UPSERT] = $actionUpsertConfig;
        } elseif ($actionUpsertConfig[ConfigUtil::UPSERT_DISABLE] ?? false) {
            $config[ConfigUtil::UPSERT] = [ConfigUtil::UPSERT_DISABLE => true];
        } elseif (\array_key_exists(ConfigUtil::UPSERT_REPLACE, $actionUpsertConfig)) {
            $config[ConfigUtil::UPSERT] = [
                ConfigUtil::UPSERT_REPLACE => $actionUpsertConfig[ConfigUtil::UPSERT_REPLACE]
            ];
        } else {
            $upsertConfig = $config[ConfigUtil::UPSERT];
            if (\array_key_exists(ConfigUtil::UPSERT_ADD, $actionUpsertConfig)
                && \array_key_exists(ConfigUtil::UPSERT_ADD, $upsertConfig)
            ) {
                $config[ConfigUtil::UPSERT][ConfigUtil::UPSERT_ADD] = array_merge(
                    $upsertConfig[ConfigUtil::UPSERT_ADD],
                    $actionUpsertConfig[ConfigUtil::UPSERT_ADD]
                );
            }
            if (\array_key_exists(ConfigUtil::UPSERT_REMOVE, $actionUpsertConfig)
                && \array_key_exists(ConfigUtil::UPSERT_REMOVE, $upsertConfig)
            ) {
                $config[ConfigUtil::UPSERT][ConfigUtil::UPSERT_REMOVE] = array_merge(
                    $upsertConfig[ConfigUtil::UPSERT_REMOVE],
                    $actionUpsertConfig[ConfigUtil::UPSERT_REMOVE]
                );
            }
        }
        if (isset($config[ConfigUtil::UPSERT])
            && ($config[ConfigUtil::UPSERT][ConfigUtil::UPSERT_DISABLE] ?? false)
            && !($actionUpsertConfig[ConfigUtil::UPSERT_DISABLE] ?? false)
        ) {
            $config[ConfigUtil::UPSERT][ConfigUtil::UPSERT_DISABLE] = false;
        }

        return $config;
    }
}
