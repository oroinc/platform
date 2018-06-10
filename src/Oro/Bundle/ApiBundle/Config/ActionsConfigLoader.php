<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The loader for "actions" configuration section.
 */
class ActionsConfigLoader extends AbstractConfigLoader
{
    private const METHOD_MAP = [
        ConfigUtil::EXCLUDE               => 'setExcluded',
        ConfigUtil::DISABLE_SORTING       => ['disableSorting', 'enableSorting'],
        ConfigUtil::DISABLE_INCLUSION     => ['disableInclusion', 'enableInclusion'],
        ConfigUtil::DISABLE_FIELDSET      => ['disableFieldset', 'enableFieldset'],
        ConfigUtil::FORM_EVENT_SUBSCRIBER => 'setFormEventSubscribers'
    ];

    private const FIELD_METHOD_MAP = [
        ConfigUtil::EXCLUDE => 'setExcluded'
    ];

    /** @var StatusCodesConfigLoader */
    protected $statusCodesConfigLoader;

    /**
     * {@inheritdoc}
     */
    public function load(array $config)
    {
        $actions = new ActionsConfig();
        foreach ($config as $key => $value) {
            if (!empty($value)) {
                $actions->addAction($key, $this->loadAction($value));
            }
        }

        return $actions;
    }

    /**
     * @param array $config
     *
     * @return ActionConfig
     */
    protected function loadAction(array $config)
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

    /**
     * @param ActionConfig $action
     * @param array        $statusCodes
     */
    protected function loadStatusCodes(ActionConfig $action, array $statusCodes)
    {
        if (!empty($statusCodes)) {
            if (null === $this->statusCodesConfigLoader) {
                $this->statusCodesConfigLoader = new StatusCodesConfigLoader();
            }
            $action->setStatusCodes($this->statusCodesConfigLoader->load($statusCodes));
        }
    }

    /**
     * @param ActionConfig $action
     * @param array|null   $fields
     */
    protected function loadFields(ActionConfig $action, array $fields = null)
    {
        if (!empty($fields)) {
            foreach ($fields as $name => $config) {
                $action->addField($name, $this->loadField($config));
            }
        }
    }

    /**
     * @param array|null $config
     *
     * @return ActionFieldConfig
     */
    protected function loadField(array $config = null)
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
