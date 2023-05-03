<?php

namespace Oro\Bundle\ApiBundle\Config\Loader;

use Oro\Bundle\ApiBundle\Config\ActionConfig;
use Oro\Bundle\ApiBundle\Config\ActionFieldConfig;
use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The loader for "actions" configuration section.
 */
class ActionsConfigLoader extends AbstractConfigLoader
{
    private const METHOD_MAP = [
        ConfigUtil::FORM_TYPE               => 'setFormType',
        ConfigUtil::FORM_OPTIONS            => 'setFormOptions',
        ConfigUtil::PAGE_SIZE               => 'setPageSize',
        ConfigUtil::MAX_RESULTS             => 'setMaxResults',
        ConfigUtil::ORDER_BY                => 'setOrderBy',
        ConfigUtil::ACL_RESOURCE            => 'setAclResource',
        ConfigUtil::DESCRIPTION             => 'setDescription',
        ConfigUtil::DOCUMENTATION           => 'setDocumentation',
        ConfigUtil::EXCLUDE                 => 'setExcluded',
        ConfigUtil::DISABLE_SORTING         => ['disableSorting', 'enableSorting'],
        ConfigUtil::DISABLE_INCLUSION       => ['disableInclusion', 'enableInclusion'],
        ConfigUtil::DISABLE_FIELDSET        => ['disableFieldset', 'enableFieldset'],
        ConfigUtil::DISABLE_META_PROPERTIES => ['disableMetaProperties', 'enableMetaProperties'],
        ConfigUtil::FORM_EVENT_SUBSCRIBER   => 'setFormEventSubscribers'
    ];

    private const FIELD_METHOD_MAP = [
        ConfigUtil::DIRECTION              => 'setDirection',
        ConfigUtil::FORM_TYPE              => 'setFormType',
        ConfigUtil::FORM_OPTIONS           => 'setFormOptions',
        ConfigUtil::EXCLUDE                => 'setExcluded',
        ConfigUtil::POST_PROCESSOR         => 'setPostProcessor',
        ConfigUtil::POST_PROCESSOR_OPTIONS => 'setPostProcessorOptions'
    ];

    private ?StatusCodesConfigLoader $statusCodesConfigLoader = null;

    /**
     * {@inheritdoc}
     */
    public function load(array $config): mixed
    {
        $actions = new ActionsConfig();
        foreach ($config as $key => $value) {
            if (!empty($value)) {
                $actions->addAction($key, $this->loadAction($value));
            }
        }

        return $actions;
    }

    private function loadAction(array $config): ActionConfig
    {
        $action = new ActionConfig();
        foreach ($config as $key => $value) {
            if (ConfigUtil::STATUS_CODES === $key) {
                $this->loadStatusCodes($action, $value);
            } elseif (ConfigUtil::FIELDS === $key) {
                $this->loadFields($action, $value);
            } else {
                $this->loadConfigValue($action, $key, $value, self::METHOD_MAP);
            }
        }

        return $action;
    }

    private function loadStatusCodes(ActionConfig $action, array $statusCodes): void
    {
        if (!empty($statusCodes)) {
            if (null === $this->statusCodesConfigLoader) {
                $this->statusCodesConfigLoader = new StatusCodesConfigLoader();
            }
            $action->setStatusCodes($this->statusCodesConfigLoader->load($statusCodes));
        }
    }

    private function loadFields(ActionConfig $action, ?array $fields): void
    {
        if (!empty($fields)) {
            foreach ($fields as $name => $config) {
                $action->addField($name, $this->loadField($config));
            }
        }
    }

    private function loadField(?array $config): ActionFieldConfig
    {
        $field = new ActionFieldConfig();
        if (!empty($config)) {
            foreach ($config as $key => $value) {
                $this->loadConfigValue($field, $key, $value, self::FIELD_METHOD_MAP);
            }
        }

        return $field;
    }
}
