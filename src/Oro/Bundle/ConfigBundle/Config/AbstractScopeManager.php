<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Entity\Config;

/**
 * A base class for configuration scope managers
 */
abstract class AbstractScopeManager
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var CacheProvider */
    protected $cache;

    /** @var array */
    protected $storedSettings = [];

    /** @var array */
    protected $changedSettings = [];

    /**
     * @param ManagerRegistry $doctrine
     * @param CacheProvider   $cache
     */
    public function __construct(ManagerRegistry $doctrine, CacheProvider $cache)
    {
        $this->doctrine = $doctrine;
        $this->cache    = $cache;
    }

    /**
     * Return config value from current scope
     *
     * @param string $name Setting name, for example "oro_user.level"
     * @param bool   $full
     *
     * @return array|string|null
     */
    public function getSettingValue($name, $full = false)
    {
        $entityId = $this->getScopeId();
        $this->ensureStoredSettingsLoaded($entityId);
        list($section, $key) = explode(ConfigManager::SECTION_MODEL_SEPARATOR, $name);

        $result = null;
        if (isset($this->storedSettings[$entityId][$section][$key])) {
            $setting = $this->storedSettings[$entityId][$section][$key];
            if (is_array($setting) && array_key_exists('value', $setting) && null !== $setting['value']) {
                if ($full) {
                    $result          = $setting;
                    $result['scope'] = $this->getScopedEntityName();
                } else {
                    $result = $setting['value'];
                }
            }
        }

        return $result;
    }

    /**
     * Get Additional Info of Config Value
     *
     * @param $name
     *
     * @return array
     */
    public function getInfo($name)
    {
        $entityId = $this->getScopeId();
        $this->ensureStoredSettingsLoaded($entityId);
        list($section, $key) = explode(ConfigManager::SECTION_MODEL_SEPARATOR, $name);

        $createdAt   = null;
        $updatedAt   = null;
        $isNullValue = true;

        if (!empty($this->storedSettings[$entityId][$section][$key])) {
            $setting = $this->storedSettings[$entityId][$section][$key];
            if (is_array($setting) && array_key_exists('value', $setting) && null !== $setting['value']) {
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
        $entityId = $this->getScopeId();
        $this->ensureStoredSettingsLoaded($entityId);
        list($section, $key) = explode(ConfigManager::SECTION_MODEL_SEPARATOR, $name);

        if (isset($this->storedSettings[$entityId][$section][$key])) {
            $this->storedSettings[$entityId][$section][$key] = array_merge(
                $this->storedSettings[$entityId][$section][$key],
                [
                    'value'                  => $value,
                    'use_parent_scope_value' => false
                ]
            );
        } else {
            $this->storedSettings[$entityId][$section][$key] = [
                'value'                  => $value,
                'use_parent_scope_value' => false
            ];
        }

        $this->changedSettings[$name] = [
            'value'                  => $value,
            'use_parent_scope_value' => false
        ];
    }

    /**
     * Reset setting value to default. To save changes in a database you need to call flush method
     *
     * @param string $name Setting name, for example "oro_user.level"
     */
    public function reset($name)
    {
        $entityId = $this->getScopeId();
        $this->ensureStoredSettingsLoaded($entityId);
        list($section, $key) = explode(ConfigManager::SECTION_MODEL_SEPARATOR, $name);

        unset($this->storedSettings[$entityId][$section][$key]);

        $this->changedSettings[$name] = [
            'use_parent_scope_value' => true
        ];
    }

    /**
     * @return array
     */
    public function getChanges()
    {
        return $this->changedSettings;
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
     *
     * @param array $settings
     *
     * @return array [updated, removed]
     */
    public function save($settings)
    {
        $entity   = $this->getScopedEntityName();
        $entityId = $this->getScopeId();

        $em = $this->doctrine->getManagerForClass('Oro\Bundle\ConfigBundle\Entity\Config');

        /** @var Config $config */
        $config = $em
            ->getRepository('Oro\Bundle\ConfigBundle\Entity\Config')
            ->findByEntity($entity, $entityId);
        if (null === $config) {
            $config = new Config();
            $config->setScopedEntity($entity)->setRecordId($entityId);
        }
        $this->storedSettings[$entityId] = $this->convertToSettings($config);

        list ($updated, $removed) = $this->calculateChangeSet($settings);
        foreach ($removed as $name) {
            list($section, $key) = explode(ConfigManager::SECTION_MODEL_SEPARATOR, $name);
            $config->removeValue($section, $key);
        }
        foreach ($updated as $name => $value) {
            list($section, $key) = explode(ConfigManager::SECTION_MODEL_SEPARATOR, $name);

            $configValue = $config->getOrCreateValue($section, $key);
            $configValue->setValue($value);

            if (!$configValue->getId()) {
                $config->getValues()->add($configValue);
            }
        }

        $em->persist($config);
        $em->flush();

        $settings = $this->convertToSettings($config);
        $this->cache->save($this->getCacheKey($entity, $entityId), $settings);
        $this->storedSettings[$entityId] = $settings;

        return [$updated, $removed];
    }

    /**
     * Calculates and returns config change set
     * Does not modify anything, so even if you call flush after calculating you will not persist any changes
     *
     * @param array $settings
     *
     * @return array [updated,              removed]
     *               [[name => value, ...], [name, ...]]
     */
    public function calculateChangeSet(array $settings)
    {
        // find new and updated
        $updated = $removed = [];
        foreach ($settings as $name => $value) {
            $currentValue = $this->getSettingValue($name, true);

            // save only if there's no default checkbox checked
            if (empty($value['use_parent_scope_value'])) {
                $updated[$name] = $value['value'];
            }

            $valueDefined      = isset($currentValue['use_parent_scope_value'])
                && $currentValue['use_parent_scope_value'] == false;
            $valueStillDefined = isset($value['use_parent_scope_value'])
                && $value['use_parent_scope_value'] == false;

            if ($valueDefined && !$valueStillDefined) {
                $removed[] = $name;
            }
        }

        return [$updated, $removed];
    }

    /**
     * Reload settings data
     */
    public function reload()
    {
        $entityId = $this->getScopeId();
        unset($this->storedSettings[$entityId]);
    }

    /**
     * @return string
     */
    abstract public function getScopedEntityName();

    /**
     * @return int
     */
    abstract public function getScopeId();

    /**
     * @param int $scopeId
     */
    public function setScopeId($scopeId)
    {
    }

    /**
     * Find scope id by provided entity object
     *
     * @param object $entity
     * @return int
     */
    public function setScopeIdFromEntity($entity)
    {
    }

    /**
     * Makes sure that settings are loaded from a database
     *
     * @param int $entityId
     */
    protected function ensureStoredSettingsLoaded($entityId)
    {
        if (!isset($this->storedSettings[$entityId])) {
            $cacheKey = $this->getCacheKey($this->getScopedEntityName(), $entityId);
            $settings = $this->cache->fetch($cacheKey);
            if (false === $settings) {
                $settings = $this->loadStoredSettings($entityId);
                $this->cache->save($cacheKey, $settings);
            }
            $this->storedSettings[$entityId] = $settings;
        }
    }

    /**
     * Loads settings from a database
     *
     * @param int $entityId
     *
     * @return Config
     */
    protected function loadStoredSettings($entityId)
    {
        $config = $this->doctrine->getManagerForClass('Oro\Bundle\ConfigBundle\Entity\Config')
            ->getRepository('Oro\Bundle\ConfigBundle\Entity\Config')
            ->findByEntity($this->getScopedEntityName(), $entityId);

        return null !== $config
            ? $this->convertToSettings($config)
            : [];
    }

    /**
     * @param Config $config
     *
     * @return array
     */
    protected function convertToSettings(Config $config)
    {
        $settings = [];
        foreach ($config->getValues() as $value) {
            $settings[$value->getSection()][$value->getName()] = [
                'value'                  => $value->getValue(),
                'use_parent_scope_value' => false,
                'createdAt'              => $value->getCreatedAt(),
                'updatedAt'              => $value->getUpdatedAt()
            ];
        }

        return $settings;
    }

    /**
     * @param string $entity
     * @param int    $entityId
     *
     * @return string
     */
    protected function getCacheKey($entity, $entityId)
    {
        return $entity . '_' . $entityId;
    }
}
