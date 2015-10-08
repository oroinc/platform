<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PropertyConfigContainer
{
    const TYPE_ENTITY = 'entity';
    const TYPE_FIELD = 'field';

    /** @var array */
    protected $config;

    /** @var array */
    protected $cache = [];

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

        if ($fieldType) {
            if (isset($this->cache['defaults'][$type][$fieldType])) {
                return $this->cache['defaults'][$type][$fieldType];
            }
        } else {
            if (isset($this->cache['defaults'][$type])) {
                return $this->cache['defaults'][$type];
            }
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
            $this->cache['defaults'][$type][$fieldType] = $result;
        } else {
            foreach ($this->config[$type]['items'] as $code => $item) {
                if (isset($item['options']['default_value'])) {
                    $result[$code] = $item['options']['default_value'];
                }
            }
            $this->cache['defaults'][$type] = $result;
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

        if (isset($this->cache['notAuditable'][$type])) {
            return $this->cache['notAuditable'][$type];
        }

        $result = [];
        foreach ($this->config[$type]['items'] as $code => $item) {
            if (isset($item['options']['auditable']) && $item['options']['auditable'] === false) {
                $result[$code] = true;
            }
        }
        $this->cache['notAuditable'][$type] = $result;

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

        if (isset($this->cache['translatable'][$type])) {
            return $this->cache['translatable'][$type];
        }

        $result = [];
        foreach ($this->config[$type]['items'] as $code => $item) {
            if (isset($item['options']['translatable']) && $item['options']['translatable'] === true) {
                $result[] = $code;
            }
        }
        $this->cache['translatable'][$type] = $result;

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

        if (isset($this->cache['indexed'][$type])) {
            return $this->cache['indexed'][$type];
        }

        $result = [];
        foreach ($this->config[$type]['items'] as $code => $item) {
            if (isset($item['options']['indexed']) && $item['options']['indexed'] === true) {
                $result[$code] = true;
            }
        }
        $this->cache['indexed'][$type] = $result;

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

        if ($fieldType) {
            if (isset($this->cache['hasForm'][$type][$fieldType])) {
                return $this->cache['hasForm'][$type][$fieldType];
            }
        } else {
            if (isset($this->cache['hasForm'][$type])) {
                return $this->cache['hasForm'][$type];
            }
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
            $this->cache['hasForm'][$type][$fieldType] = $result;
        } else {
            foreach ($this->config[$type]['items'] as $code => $item) {
                if (isset($item['form']['type'])) {
                    $result = true;
                    break;
                }
            }
            $this->cache['hasForm'][$type] = $result;
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

        if ($fieldType) {
            if (isset($this->cache['formItems'][$type][$fieldType])) {
                return $this->cache['formItems'][$type][$fieldType];
            }
        } else {
            if (isset($this->cache['formItems'][$type])) {
                return $this->cache['formItems'][$type];
            }
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
            $this->cache['formItems'][$type][$fieldType] = $result;
        } else {
            foreach ($this->config[$type]['items'] as $code => $item) {
                if (isset($item['form']['type'])) {
                    $result[$code] = $item;
                }
            }
            $this->cache['formItems'][$type] = $result;
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

        if (isset($this->cache['required'][$type])) {
            return $this->cache['required'][$type];
        }

        $result = [];
        foreach ($this->config[$type]['items'] as $code => $item) {
            if (isset($item['options']['required_property'])) {
                $result[$code] = $item['options']['required_property'];
            }
        }
        $this->cache['required'][$type] = $result;

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
