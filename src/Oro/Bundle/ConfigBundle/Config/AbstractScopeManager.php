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
     * @param bool $full
     * @param null|int $entityId
     *
     * @return array|null|string
     */
    public function getSettingValue($name, $full = false, $entityId = null)
    {
        if (null === $entityId) {
            $entityId = $this->getScopeId();
        }
        $this->ensureStoredSettingsLoaded($entityId);
        list($section, $key) = explode(ConfigManager::SECTION_MODEL_SEPARATOR, $name);

        $result = null;
        if (isset($this->storedSettings[$entityId][$section][$key])) {
            $setting = $this->storedSettings[$entityId][$section][$key];
            if (isset($setting['value'])) {
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
     * @param string $name
     * @param null|int $entityId
     *
     * @return array
     */
    public function getInfo($name, $entityId = null)
    {
        if (null === $entityId) {
            $entityId = $this->getScopeId();
        }
        $this->ensureStoredSettingsLoaded($entityId);
        list($section, $key) = explode(ConfigManager::SECTION_MODEL_SEPARATOR, $name);

        $createdAt   = null;
        $updatedAt   = null;
        $isNullValue = true;

        if (!empty($this->storedSettings[$entityId][$section][$key])) {
            $setting = $this->storedSettings[$entityId][$section][$key];
            if (isset($setting['value'])) {
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
     * @param string $name Setting name, for example "oro_user.level"
     * @param mixed $value Setting value
     * @param null|int $entityId
     */
    public function set($name, $value, $entityId = null)
    {
        if (null === $entityId) {
            $entityId = $this->getScopeId();
        }
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

        $this->changedSettings[$entityId][$name] = [
            'value'                  => $value,
            'use_parent_scope_value' => false
        ];
    }

    /**
     * Reset setting value to default. To save changes in a database you need to call flush method
     *
     * @param string $name Setting name, for example "oro_user.level"
     * @param null|int $entityId
     */
    public function reset($name, $entityId = null)
    {
        if (null === $entityId) {
            $entityId = $this->getScopeId();
        }
        $this->ensureStoredSettingsLoaded($entityId);
        list($section, $key) = explode(ConfigManager::SECTION_MODEL_SEPARATOR, $name);

        unset($this->storedSettings[$entityId][$section][$key]);
        $this->cache->delete($this->getCacheKey($this->getScopedEntityName(), $entityId));

        $this->changedSettings[$entityId][$name] = [
            'use_parent_scope_value' => true
        ];
    }

    /**
     * @param null|int $entityId
     * @return array
     */
    public function getChanges($entityId = null)
    {
        if (null === $entityId) {
            $entityId = $this->getScopeId();
        }
        if (array_key_exists($entityId, $this->changedSettings)) {
            return $this->changedSettings[$entityId];
        }
        
        return [];
    }

    /**
     * Save changes made with set or reset methods in a database
     * @param null|int $entityId
     */
    public function flush($entityId = null)
    {
        if (null === $entityId) {
            $entityId = $this->getScopeId();
        }
        if (count($this->changedSettings[$entityId]) > 0) {
            $this->save($this->changedSettings[$entityId]);
            $this->changedSettings[$entityId] = [];
        }
    }

    /**
     * Save settings with fallback to global scope (default)
     *
     * @param array $settings
     * @param null|int $entityId
     *
     * @return array [updated, removed]
     */
    public function save($settings, $entityId = null)
    {
        $entity   = $this->getScopedEntityName();
        if (null === $entityId) {
            $entityId = $this->getScopeId();
        }

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

        list ($updated, $removed) = $this->calculateChangeSet($settings, $entityId);
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
     * @param null|int $entityId
     *
     * @return array [updated,              removed]
     *               [[name => value, ...], [name, ...]]
     */
    public function calculateChangeSet(array $settings, $entityId = null)
    {
        // find new and updated
        $updated = $removed = [];
        foreach ($settings as $name => $value) {
            $currentValue = $this->getSettingValue($name, true, $entityId);
            $useCurrentScope = empty($value['use_parent_scope_value']);

            // save only if there's no default checkbox checked
            if ($useCurrentScope) {
                $updated[$name] = $value['value'];
            }

            $valueDefined = empty($currentValue['use_parent_scope_value']);
            if ($valueDefined && !$useCurrentScope) {
                $removed[] = $name;
            }
        }

        return [$updated, $removed];
    }

    /**
     * Reload settings data
     * @param null|int $entityId
     */
    public function reload($entityId = null)
    {
        if (null === $entityId) {
            $entityId = $this->getScopeId();
        }
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
     * @return string
     */
    public function getScopeInfo()
    {
        return '';
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
            if (!$this->cache->contains($cacheKey)) {
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

        if (null === $config) {
            return [];
        }

        return $this->convertToSettings($config);
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
