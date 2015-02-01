<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;

/**
 * Abstract class for configuration scope
 */
abstract class AbstractScopeManager
{
    /** @var ObjectManager */
    protected $om;

    /** @var array */
    protected $storedSettings = [];

    /** @var array */
    protected $changedSettings = [];

    /**
     * @param ObjectManager $om
     */
    public function __construct(
        ObjectManager $om
    ) {
        $this->om = $om;
    }

    /**
     * Return config value from current scope
     *
     * @param string $name Setting name, for example "oro_user.level"
     * @param bool   $full
     * @return array|string|null
     */
    public function getSettingValue($name, $full = false)
    {
        $entity   = $this->getScopedEntityName();
        $entityId = $this->getScopeId();
        $this->loadStoredSettings($entity, $entityId);
        list($section, $key) = explode(ConfigManager::SECTION_MODEL_SEPARATOR, $name);

        if (isset($this->storedSettings[$entity][$entityId][$section][$key])) {
            $setting = $this->storedSettings[$entity][$entityId][$section][$key];
            if (is_array($setting) && array_key_exists('value', $setting) && !is_null($setting['value'])) {
                return !$full ? $setting['value'] : $setting;
            }
        }

        return null;
    }

    /**
     * Get Additional Info of Config Value
     *
     * @param $name
     * @return array
     */
    public function getInfo($name)
    {
        $entity   = $this->getScopedEntityName();
        $entityId = $this->getScopeId();
        $this->loadStoredSettings($entity, $entityId);
        list($section, $key) = explode(ConfigManager::SECTION_MODEL_SEPARATOR, $name);

        $createdAt   = null;
        $updatedAt   = null;
        $isNullValue = true;

        if (!empty($this->storedSettings[$entity][$entityId][$section][$key])) {
            $setting = $this->storedSettings[$entity][$entityId][$section][$key];
            if (is_array($setting) && array_key_exists('value', $setting) && !is_null($setting['value'])) {
                $isNullValue = false;
                if (array_key_exists('createdAt', $setting)) {
                    $createdAt = $setting['createdAt'];
                }
                if (array_key_exists('updatedAt', $setting)) {
                    $updatedAt = $setting['updatedAt'];
                }
            }
        }

        return [$createdAt, $updatedAt, $isNullValue];
    }

    /**
     * Set setting value. To save changes in a database you need to call flush method
     *
     * @param string $name  Setting name, for example "oro_user.level"
     * @param mixed  $value Setting value
     */
    public function set($name, $value)
    {
        $entity   = $this->getScopedEntityName();
        $entityId = $this->getScopeId();
        $this->loadStoredSettings($entity, $entityId);

        $changeKey = str_replace(ConfigManager::SECTION_MODEL_SEPARATOR, ConfigManager::SECTION_VIEW_SEPARATOR, $name);

        $this->changedSettings[$changeKey] = ['value' => $value, 'use_parent_scope_value' => false];
    }

    /**
     * Reset setting value to default. To save changes in a database you need to call flush method
     *
     * @param string $name Setting name, for example "oro_user.level"
     */
    public function reset($name)
    {
        $entity   = $this->getScopedEntityName();
        $entityId = $this->getScopeId();
        $this->loadStoredSettings($entity, $entityId);

        list($section, $key) = explode(ConfigManager::SECTION_MODEL_SEPARATOR, $name);
        unset($this->storedSettings[$entity][$entityId][$section][$key]);

        $changeKey = str_replace(ConfigManager::SECTION_MODEL_SEPARATOR, ConfigManager::SECTION_VIEW_SEPARATOR, $name);
        $this->changedSettings[$changeKey] = ['use_parent_scope_value' => true];
    }

    /**
     * Save changes made with set or reset methods in a database
     */
    public function flush()
    {
        if (!empty($this->changedSettings)) {
            $this->save($this->changedSettings);
            $this->changedSettings = [];
        }
    }

    /**
     * Save settings with fallback to global scope (default)
     */
    public function save($newSettings)
    {
        /** @var Config $config */
        $config = $this->om
            ->getRepository('OroConfigBundle:Config')
            ->getByEntity($this->getScopedEntityName(), $this->getScopeId());

        list ($updated, $removed) = $this->calculateChangeSet($newSettings);
        /** @var ConfigValue $value */
        if (!empty($removed)) {
            foreach ($removed as $removedItemValue) {
                $value = $config->getValue($removedItemValue[0], $removedItemValue[1]);
                if ($value) {
                    $value->clearValue();
                }
            }
        }

        foreach ($updated as $newItemKey => $newItemValue) {
            $newItemKey   = explode(ConfigManager::SECTION_VIEW_SEPARATOR, $newItemKey);
            $newItemValue = is_array($newItemValue) ? $newItemValue['value'] : $newItemValue;

            $value = $config->getOrCreateValue($newItemKey[0], $newItemKey[1]);
            $value->setValue($newItemValue);

            if (!$value->getId()) {
                $config->getValues()->add($value);
            }
        }

        $this->om->persist($config);
        $this->om->flush();

        return [$updated, $removed];
    }

    /**
     * Calculates and returns config change set
     * Does not modify anything, so even if you call flush after calculating you will not persist any changes
     *
     * @param $newSettings
     *
     * @return array
     */
    public function calculateChangeSet($newSettings)
    {
        // find new and updated
        $updated = $removed = [];
        foreach ($newSettings as $key => $value) {
            $currentValue = $this->getSettingValue(
                str_replace(ConfigManager::SECTION_VIEW_SEPARATOR, ConfigManager::SECTION_MODEL_SEPARATOR, $key),
                true
            );

            // save only if there's no default checkbox checked
            if (empty($value['use_parent_scope_value'])) {
                $updated[$key] = $value;
            }

            $valueDefined      = isset($currentValue['use_parent_scope_value'])
                && $currentValue['use_parent_scope_value'] == false;
            $valueStillDefined = isset($value['use_parent_scope_value'])
                && $value['use_parent_scope_value'] == false;

            if ($valueDefined && !$valueStillDefined) {
                $removed[] = array_slice(explode(ConfigManager::SECTION_VIEW_SEPARATOR, $key), 0, 2);
            }
        }

        return [$updated, $removed];
    }

    /**
     * @param string $entity
     * @param int    $entityId
     *
     * @return bool
     */
    public function loadStoredSettings($entity, $entityId)
    {
        if (isset($this->storedSettings[$entity][$entityId])) {
            return false;
        }

        $config = $this->om
            ->getRepository('OroConfigBundle:Config')
            ->loadSettings($entity, $entityId);

        $this->storedSettings[$entity][$entityId] = $config;

        return true;
    }

    /**
     * Reload settings data
     */
    public function reload()
    {
        $entity   = $this->getScopedEntityName();
        $entityId = $this->getScopeId();
        unset($this->storedSettings[$entity][$entityId]);
        $this->loadStoredSettings($entity, $entityId);
    }

    /**
     * @return string
     */
    abstract public function getScopedEntityName();

    /**
     * @return int
     */
    abstract public function getScopeId();
}
