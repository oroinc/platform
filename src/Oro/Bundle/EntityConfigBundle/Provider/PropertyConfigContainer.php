<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PropertyConfigContainer
{
    /**
     * Type Of Config
     */
    const TYPE_ENTITY = 'entity';
    const TYPE_FIELD = 'field';

    /** @var array */
    protected $config;

    /**
     * @param array $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * Gets all configuration values for the given config type
     *
     * @param string|ConfigIdInterface $type
     *
     * @return array
     */
    public function getItems($type = self::TYPE_ENTITY)
    {
        $type = $this->getConfigType($type);

        return isset($this->config[$type]['items'])
            ? $this->config[$type]['items']
            : [];
    }

    /**
     * @param string|ConfigIdInterface $type
     * @param string|null              $fieldType
     *
     * @return array
     */
    public function getDefaultValues($type = self::TYPE_ENTITY, $fieldType = null)
    {
        $type = $this->getConfigType($type);

        if (empty($this->config[$type]['items'])) {
            return [];
        }

        $result = [];
        if ($fieldType) {
            foreach ($this->config[$type]['items'] as $code => $item) {
                if (isset($item['options']['default_value'])
                    && (
                        !isset($item['options']['allowed_type'])
                        || in_array($fieldType, $item['options']['allowed_type'], true)
                    )
                ) {
                    $result[$code] = $item['options']['default_value'];
                }
            }
        } else {
            foreach ($this->config[$type]['items'] as $code => $item) {
                if (isset($item['options']['default_value'])) {
                    $result[$code] = $item['options']['default_value'];
                }
            }
        }

        return $result;
    }

    /**
     * @param string|ConfigIdInterface $type
     *
     * @return array
     */
    public function getNotAuditableValues($type = self::TYPE_ENTITY)
    {
        $type = $this->getConfigType($type);

        if (empty($this->config[$type]['items'])) {
            return [];
        }

        $result = [];
        foreach ($this->config[$type]['items'] as $code => $item) {
            if (isset($item['options']['auditable']) && $item['options']['auditable'] === false) {
                $result[$code] = true;
            }
        }

        return $result;
    }

    /**
     * Get translatable property's codes
     *
     * @param string|ConfigIdInterface $type
     *
     * @return array
     */
    public function getTranslatableValues($type = self::TYPE_FIELD)
    {
        $type = $this->getConfigType($type);

        if (empty($this->config[$type]['items'])) {
            return [];
        }

        $result = [];
        foreach ($this->config[$type]['items'] as $code => $item) {
            if (isset($item['options']['translatable']) && $item['options']['translatable'] === true) {
                $result[] = $code;
            }
        }

        return $result;
    }

    /**
     * @param string|ConfigIdInterface $type
     *
     * @return array
     */
    public function getIndexedValues($type = self::TYPE_ENTITY)
    {
        $type = $this->getConfigType($type);

        if (empty($this->config[$type]['items'])) {
            return [];
        }

        $result = [];
        foreach ($this->config[$type]['items'] as $code => $item) {
            if (isset($item['options']['indexed']) && $item['options']['indexed'] === true) {
                $result[$code] = true;
            }
        }

        return $result;
    }

    /**
     * @param string|ConfigIdInterface $type
     * @param string|null              $fieldType
     *
     * @return bool
     */
    public function hasForm($type = self::TYPE_ENTITY, $fieldType = null)
    {
        $type = $this->getConfigType($type);

        if (empty($this->config[$type]['items'])) {
            return false;
        }

        $result = false;
        if ($fieldType) {
            foreach ($this->config[$type]['items'] as $code => $item) {
                if (isset($item['form']['type'])
                    && (
                        !isset($item['options']['allowed_type'])
                        || in_array($fieldType, $item['options']['allowed_type'], true)
                    )
                ) {
                    $result = true;
                    break;
                }
            }
        } else {
            foreach ($this->config[$type]['items'] as $code => $item) {
                if (isset($item['form']['type'])) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param string|ConfigIdInterface $type
     * @param string|null              $fieldType
     *
     * @return bool
     */
    public function getFormItems($type = self::TYPE_ENTITY, $fieldType = null)
    {
        $type = $this->getConfigType($type);

        if (empty($this->config[$type]['items'])) {
            return [];
        }

        $result = [];
        if ($fieldType) {
            foreach ($this->config[$type]['items'] as $code => $item) {
                if (isset($item['form']['type'])
                    && (
                        !isset($item['options']['allowed_type'])
                        || in_array($fieldType, $item['options']['allowed_type'], true)
                    )
                ) {
                    $result[$code] = $item;
                }
            }
        } else {
            foreach ($this->config[$type]['items'] as $code => $item) {
                if (isset($item['form']['type'])) {
                    $result[$code] = $item;
                }
            }
        }

        return $result;
    }

    /**
     * @param string|ConfigIdInterface $type
     *
     * @return array
     */
    public function getFormConfig($type = self::TYPE_ENTITY)
    {
        $type = $this->getConfigType($type);

        return isset($this->config[$type]['form'])
            ? $this->config[$type]['form']
            : [];
    }

    /**
     * @param string|ConfigIdInterface $type
     *
     * @return array
     */
    public function getFormBlockConfig($type = self::TYPE_ENTITY)
    {
        $type = $this->getConfigType($type);

        return isset($this->config[$type]['form']['block_config'])
            ? $this->config[$type]['form']['block_config']
            : null;
    }

    /**
     * @param string|ConfigIdInterface $type
     *
     * @return array
     */
    public function getGridActions($type = self::TYPE_ENTITY)
    {
        $type = $this->getConfigType($type);

        return isset($this->config[$type]['grid_action'])
            ? $this->config[$type]['grid_action']
            : [];
    }

    /**
     * @param string|ConfigIdInterface $type
     *
     * @return array
     */
    public function getUpdateActionFilter($type = self::TYPE_ENTITY)
    {
        $type = $this->getConfigType($type);

        return isset($this->config[$type]['update_filter'])
            ? $this->config[$type]['update_filter']
            : null;
    }

    /**
     * @param string|ConfigIdInterface $type
     *
     * @return array
     */
    public function getLayoutActions($type = self::TYPE_ENTITY)
    {
        $type = $this->getConfigType($type);

        return isset($this->config[$type]['layout_action'])
            ? $this->config[$type]['layout_action']
            : [];
    }

    /**
     * @param string|ConfigIdInterface $type
     *
     * @return array
     */
    public function getRequiredPropertyValues($type = self::TYPE_ENTITY)
    {
        $type = $this->getConfigType($type);

        if (empty($this->config[$type]['items'])) {
            return [];
        }

        $result = [];
        foreach ($this->config[$type]['items'] as $code => $item) {
            if (isset($item['options']['required_property'])) {
                $result[$code] = $item['options']['required_property'];
            }
        }

        return $result;
    }

    /**
     * @param string|ConfigIdInterface $type
     *
     * @return array
     */
    public function getRequireJsModules($type = self::TYPE_ENTITY)
    {
        $type = $this->getConfigType($type);

        return isset($this->config[$type]['require_js'])
            ? $this->config[$type]['require_js']
            : [];
    }

    /**
     * Indicates whether the schema update is required if an attribute with the given code is modified
     *
     * @param string                   $code The attribute name
     * @param string|ConfigIdInterface $type
     *
     * @return bool
     */
    public function isSchemaUpdateRequired($code, $type = self::TYPE_ENTITY)
    {
        $type = $this->getConfigType($type);

        return
            isset($this->config[$type]['items'][$code]['options']['require_schema_update'])
            && $this->config[$type]['items'][$code]['options']['require_schema_update'] === true;
    }

    /**
     * Gets a string represents a type of a config
     *
     * @param string|ConfigIdInterface $type
     *
     * @return string
     */
    protected function getConfigType($type)
    {
        if ($type instanceof ConfigIdInterface) {
            return $type instanceof FieldConfigId
                ? PropertyConfigContainer::TYPE_FIELD
                : PropertyConfigContainer::TYPE_ENTITY;
        }

        return $type;
    }
}
