<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Event\ConfigManagerScopeIdUpdateEvent;

/**
 * A base class for configuration scope managers
 */
abstract class AbstractScopeManager
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var CacheProvider */
    protected $cache;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var array */
    protected $changedSettings = [];

    /**
     * @param ManagerRegistry $doctrine
     * @param CacheProvider   $cache
     */
    public function __construct(
        ManagerRegistry $doctrine,
        CacheProvider $cache,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->doctrine = $doctrine;
        $this->cache    = $cache;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Return config value from current scope
     *
     * @param string $name Setting name, for example "oro_user.level"
     * @param bool $full
     * @param null|int|object $scopeIdentifier
     *
     * @return array|null|string
     */
    public function getSettingValue($name, $full = false, $scopeIdentifier = null)
    {
        $entityId = $this->resolveIdentifier($scopeIdentifier);
        $setting = $this->getCachedSetting($entityId, $name);

        $result = null;
        if (null !== $setting && isset($setting['value'])) {
            if ($full) {
                $result          = $setting;
                $result['scope'] = $this->getScopedEntityName();
            } else {
                $result = $setting['value'];
            }
        }

        return $result;
    }

    /**
     * Get Additional Info of Config Value
     *
     * @param string $name
     * @param null|int|object $scopeIdentifier
     *
     * @return array
     */
    public function getInfo($name, $scopeIdentifier = null)
    {
        $entityId = $this->resolveIdentifier($scopeIdentifier);
        $setting = $this->getCachedSetting($entityId, $name);

        $createdAt   = null;
        $updatedAt   = null;
        $isNullValue = true;

        if (null !== $setting) {
            $isNullValue = false;
            if (array_key_exists('createdAt', $setting)) {
                $createdAt = $setting['createdAt'];
            }
            if (array_key_exists('updatedAt', $setting)) {
                $updatedAt = $setting['updatedAt'];
            }
        }

        return [$createdAt, $updatedAt, $isNullValue];
    }

    /**
     * @param int|null $entityId
     * @param string $name
     * @return array|null
     */
    protected function getCachedSetting($entityId, $name)
    {
        $cacheKey = $this->getCacheKey($this->getScopedEntityName(), $entityId);
        list($section, $key) = explode(ConfigManager::SECTION_MODEL_SEPARATOR, $name);

        if (!$this->cache->contains($cacheKey)) {
            $settings = $this->loadStoredSettings($entityId);
            $this->cache->save($cacheKey, $settings);
        }

        $settings = $this->cache->fetch($cacheKey);
        $keySetting = null;

        if (!empty($settings[$section][$key])) {
            $keySetting = $settings[$section][$key];
        }

        if (isset($this->changedSettings[$entityId][$name]['value'])) {
            if (null === $keySetting) {
                $keySetting = [];
            }
            $keySetting = array_merge($keySetting, $this->changedSettings[$entityId][$name]);
        }

        return $keySetting;
    }

    /**
     * Set setting value. To save changes in a database you need to call flush method
     *
     * @param string $name Setting name, for example "oro_user.level"
     * @param mixed $value Setting value
     * @param null|int|object $scopeIdentifier
     */
    public function set($name, $value, $scopeIdentifier = null)
    {
        $entityId = $this->resolveIdentifier($scopeIdentifier);

        $this->changedSettings[$entityId][$name] = [
            'value'                  => $value,
            'use_parent_scope_value' => false
        ];
    }

    /**
     * Reset setting value to default. To save changes in a database you need to call flush method
     *
     * @param string $name Setting name, for example "oro_user.level"
     * @param null|int|object $scopeIdentifier
     */
    public function reset($name, $scopeIdentifier = null)
    {
        $entityId = $this->resolveIdentifier($scopeIdentifier);

        $this->cache->delete($this->getCacheKey($this->getScopedEntityName(), $entityId));

        $this->changedSettings[$entityId][$name] = [
            'use_parent_scope_value' => true
        ];
    }

    /**
     * @param null|int $scopeIdentifier
     * @return array
     */
    public function getChanges($scopeIdentifier = null)
    {
        $entityId = $this->resolveIdentifier($scopeIdentifier);
        if (array_key_exists($entityId, $this->changedSettings)) {
            return $this->changedSettings[$entityId];
        }
        
        return [];
    }

    /**
     * Save changes made with set or reset methods in a database
     * @param null|int|object $scopeIdentifier
     */
    public function flush($scopeIdentifier = null)
    {
        $entityId = $this->resolveIdentifier($scopeIdentifier);
        if (!empty($this->changedSettings[$entityId])) {
            $this->save($this->changedSettings[$entityId], $scopeIdentifier);
            $this->changedSettings[$entityId] = [];
        }
    }

    /**
     * Save settings with fallback to global scope (default)
     *
     * @param array $settings
     * @param null|int|object $scopeIdentifier
     *
     * @return array [updated, removed]
     */
    public function save($settings, $scopeIdentifier = null)
    {
        $entity   = $this->getScopedEntityName();
        $entityId = $this->resolveIdentifier($scopeIdentifier);

        $em = $this->doctrine->getManagerForClass('Oro\Bundle\ConfigBundle\Entity\Config');

        /** @var Config $config */
        $config = $em
            ->getRepository('Oro\Bundle\ConfigBundle\Entity\Config')
            ->findByEntity($entity, $entityId);
        if (null === $config) {
            $config = new Config();
            $config->setScopedEntity($entity)->setRecordId($entityId);
        }

        list($updated, $removed) = $this->calculateChangeSet($settings, $entityId);
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

        foreach ($settings as $name => $value) {
            unset($this->changedSettings[$entityId][$name]);
        }

        $settings = SettingsConverter::convertToSettings($config);
        $this->cache->save($this->getCacheKey($entity, $entityId), $settings);

        $em->detach($config);

        return [$updated, $removed];
    }

    /**
     * Calculates and returns config change set
     * Does not modify anything, so even if you call flush after calculating you will not persist any changes
     *
     * @param array $settings
     * @param null|int|object $scopeIdentifier
     *
     * @return array [updated,              removed]
     *               [[name => value, ...], [name, ...]]
     */
    public function calculateChangeSet(array $settings, $scopeIdentifier = null)
    {
        // find new and updated
        $updated = $removed = [];
        foreach ($settings as $name => $value) {
            $entityId = $this->resolveIdentifier($scopeIdentifier);
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
     * @param null|int|object $scopeIdentifier
     */
    public function reload($scopeIdentifier = null)
    {
        $entityId = $this->resolveIdentifier($scopeIdentifier);
        $cacheKey = $this->getCacheKey($this->getScopedEntityName(), $entityId);

        $settings = $this->loadStoredSettings($entityId);
        $this->cache->save($cacheKey, $settings);
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

    protected function dispatchScopeIdChangeEvent()
    {
        $event = new ConfigManagerScopeIdUpdateEvent();
        $this->eventDispatcher->dispatch(ConfigManagerScopeIdUpdateEvent::EVENT_NAME, $event);
    }

    /**
     * @return string
     */
    public function getScopeInfo()
    {
        return '';
    }

    /**
     * @param object $entity
     * @return int|null
     */
    public function getScopeIdFromEntity($entity)
    {
        if ($this->isSupportedScopeEntity($entity)) {
            return $this->getScopeEntityIdValue($entity);
        }

        return $this->getScopeId();
    }

    /**
     * Find scope id by provided entity object
     *
     * @param object $entity
     */
    public function setScopeIdFromEntity($entity)
    {
        $scopeId = $this->getScopeIdFromEntity($entity);

        if ($scopeId) {
            $this->setScopeId($scopeId);
        }
    }

    /**
     * @param object $entity
     * @return bool
     */
    protected function isSupportedScopeEntity($entity)
    {
        return false;
    }

    /**
     * @param object $entity
     * @return mixed
     */
    protected function getScopeEntityIdValue($entity)
    {
        return null;
    }

    /**
     * Loads settings from a database
     *
     * @param int $entityId
     *
     * @return array
     */
    protected function loadStoredSettings($entityId)
    {
        $config = $this->doctrine->getManagerForClass('Oro\Bundle\ConfigBundle\Entity\Config')
            ->getRepository('Oro\Bundle\ConfigBundle\Entity\Config')
            ->findByEntity($this->getScopedEntityName(), $entityId);

        if (null === $config) {
            return [];
        }

        return SettingsConverter::convertToSettings($config);
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

    /**
     * @param null|int|object $identifier
     * @return int|null
     */
    public function resolveIdentifier($identifier)
    {
        if (is_object($identifier)) {
            $identifier = $this->getScopeIdFromEntity($identifier);
        }
        if (null === $identifier) {
            $identifier = $this->getScopeId();
        }

        return $identifier;
    }
}
