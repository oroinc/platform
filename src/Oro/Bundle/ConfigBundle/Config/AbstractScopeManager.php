<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Event\ConfigManagerScopeIdUpdateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The base class for configuration scope managers.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractScopeManager
{
    protected ManagerRegistry $doctrine;
    protected CacheInterface $cache;
    protected EventDispatcherInterface $eventDispatcher;
    protected ConfigBag $configBag;
    protected array $changedSettings = [];

    public function __construct(
        ManagerRegistry $doctrine,
        CacheInterface $cache,
        EventDispatcherInterface $eventDispatcher,
        ConfigBag $configBag,
    ) {
        $this->doctrine = $doctrine;
        $this->cache = $cache;
        $this->eventDispatcher = $eventDispatcher;
        $this->configBag = $configBag;
    }

    /**
     * Return config value from current scope
     */
    public function getSettingValue(
        string $name,
        bool $full = false,
        mixed $scopeIdentifier = null,
        bool $skipChanges = false
    ): array|null|string {
        $entityId = $this->resolveIdentifier($scopeIdentifier);
        $setting = $this->getCachedSetting($entityId, $name, $skipChanges);

        $result = null;

        if ($setting === null) {
            return null;
        }

        if ($setting[ConfigManager::VALUE_KEY] !== null
            || $setting[ConfigManager::USE_PARENT_SCOPE_VALUE_KEY] === false
        ) {
            $result = $setting[ConfigManager::VALUE_KEY];
            if ($full) {
                $result = $setting;
                $result[ConfigManager::SCOPE_KEY] = $this->getScopedEntityName();
            }
        }

        return $result;
    }

    /**
     * Get Additional Info of Config Value
     */
    public function getInfo(string $name, mixed $scopeIdentifier = null): array
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

    protected function getCachedSetting(?int $entityId, string $name, bool $skipChanges = false): ?array
    {
        $cacheKey = $this->getCacheKey($this->getScopedEntityName(), $entityId);
        [$section, $key] = explode(ConfigManager::SECTION_MODEL_SEPARATOR, $name);

        $settings = $this->cache->get($cacheKey, function () use ($entityId) {
            return $this->loadStoredSettings($entityId);
        });

        $keySetting = null;

        if (!empty($settings[$section][$key])) {
            $keySetting = $settings[$section][$key];
        }

        if (!$skipChanges && isset($this->changedSettings[$entityId][$name][ConfigManager::VALUE_KEY])) {
            if (null === $keySetting) {
                $keySetting = [];
            }
            $keySetting = array_merge($keySetting, $this->changedSettings[$entityId][$name]);
        }

        return $keySetting;
    }

    /**
     * Set setting value. To save changes in a database you need to call flush method
     */
    public function set(string $name, mixed $value, mixed $scopeIdentifier = null): void
    {
        $entityId = $this->resolveIdentifier($scopeIdentifier);

        $this->changedSettings[$entityId][$name] = [
            ConfigManager::VALUE_KEY                  => $value,
            ConfigManager::USE_PARENT_SCOPE_VALUE_KEY => false
        ];
    }

    /**
     * Reset setting value to default. To save changes in a database you need to call flush method
     */
    public function reset(string $name, mixed $scopeIdentifier = null): void
    {
        $entityId = $this->resolveIdentifier($scopeIdentifier);

        $this->cache->delete($this->getCacheKey($this->getScopedEntityName(), $entityId));

        $this->changedSettings[$entityId][$name] = [
            ConfigManager::USE_PARENT_SCOPE_VALUE_KEY => true
        ];
    }

    /**
     * Removes scope settings. To save changes in a database, a flush method should be called
     */
    public function deleteScope(mixed $scopeIdentifier): void
    {
        $entity   = $this->getScopedEntityName();
        $entityId = $this->resolveIdentifier($scopeIdentifier);

        /** @var Config $config */
        $config = $this->doctrine->getManagerForClass(Config::class)
            ->getRepository(Config::class)
            ->findByEntity($entity, $entityId);

        if ($config) {
            foreach ($config->getValues() as $value) {
                $name = $value->getSection() . ConfigManager::SECTION_MODEL_SEPARATOR . $value->getName();
                $this->changedSettings[$entityId][$name] = [
                    ConfigManager::USE_PARENT_SCOPE_VALUE_KEY => true
                ];
            }
        }

        $this->cache->delete($this->getCacheKey($entity, $entityId));
    }

    public function getChanges(mixed $scopeIdentifier = null): array
    {
        $entityId = $this->resolveIdentifier($scopeIdentifier);
        if (array_key_exists($entityId, $this->changedSettings)) {
            return $this->changedSettings[$entityId];
        }

        return [];
    }

    public function getChangedScopeIdentifiers(): array
    {
        return array_keys($this->changedSettings);
    }

    /**
     * Save changes made with set or reset methods in a database
     */
    public function flush(mixed $scopeIdentifier = null): void
    {
        $entityId = $this->resolveIdentifier($scopeIdentifier);
        if (!empty($this->changedSettings[$entityId])) {
            $this->save($this->changedSettings[$entityId], $scopeIdentifier);
            $this->changedSettings[$entityId] = [];
        }
    }

    /**
     * Save settings with fallback to global scope (default)
     */
    public function save(array $settings, mixed $scopeIdentifier = null): array
    {
        $entity   = $this->getScopedEntityName();
        $entityId = $this->resolveIdentifier($scopeIdentifier);

        $em = $this->doctrine->getManagerForClass(Config::class);

        /** @var Config $config */
        $config = $em
            ->getRepository(Config::class)
            ->findByEntity($entity, $entityId);
        if (null === $config) {
            $config = new Config();
            $config->setScopedEntity($entity)->setRecordId($entityId);
        }

        [$updated, $removed] = $this->calculateChangeSet($settings, $entityId);
        foreach ($removed as $name) {
            [$section, $key] = explode(ConfigManager::SECTION_MODEL_SEPARATOR, $name);
            $config->removeValue($section, $key);
        }
        foreach ($updated as $name => $value) {
            [$section, $key] = explode(ConfigManager::SECTION_MODEL_SEPARATOR, $name);

            $configValue = $config->getOrCreateValue($section, $key);
            $configValue->setValue($value);

            if (!$configValue->getId()) {
                $config->getValues()->add($configValue);
            }
        }
        if (0 === $config->getValues()->count()) {
            $em->remove($config);
        } else {
            $em->persist($config);
        }

        $em->flush();

        foreach ($settings as $name => $value) {
            unset($this->changedSettings[$entityId][$name]);
        }

        $settings = $this->normalizeSettings(SettingsConverter::convertToSettings($config));
        $cacheKey = $this->getCacheKey($entity, $entityId);
        $this->cache->delete($cacheKey);
        $this->cache->get($cacheKey, function () use ($settings) {
            return $settings;
        });

        $em->detach($config);

        return [$updated, $removed];
    }

    /**
     * Calculates and returns config change set
     * Does not modify anything, so even if you call flush after calculating you will not persist any changes
     *
     * @return array [updated,              removed]
     *               [[name => value, ...], [name, ...]]
     */
    public function calculateChangeSet(array $settings, mixed $scopeIdentifier = null): array
    {
        // find new and updated
        $updated = $removed = [];
        foreach ($settings as $name => $value) {
            $entityId = $this->resolveIdentifier($scopeIdentifier);
            $currentValue = $this->getSettingValue($name, true, $entityId);
            $useCurrentScope = empty($value[ConfigManager::USE_PARENT_SCOPE_VALUE_KEY]);

            // save only if there's no default checkbox checked
            if ($useCurrentScope) {
                $updated[$name] = $value[ConfigManager::VALUE_KEY];
            }

            $valueDefined = empty($currentValue[ConfigManager::USE_PARENT_SCOPE_VALUE_KEY]);
            if ($valueDefined && !$useCurrentScope) {
                $removed[] = $name;
            }
        }

        return [$updated, $removed];
    }

    /**
     * Reload settings data
     */
    public function reload(mixed $scopeIdentifier = null): void
    {
        $this->resetCache();

        $entityId = $this->resolveIdentifier($scopeIdentifier);
        $cacheKey = $this->getCacheKey($this->getScopedEntityName(), $entityId);

        $settings = $this->loadStoredSettings($entityId);
        $this->cache->delete($cacheKey);
        $this->cache->get($cacheKey, function () use ($settings) {
            return $settings;
        });

        $event = new ConfigManagerScopeIdUpdateEvent();
        $this->eventDispatcher->dispatch($event, ConfigManagerScopeIdUpdateEvent::EVENT_NAME);
    }

    abstract public function getScopedEntityName(): string;

    abstract public function getScopeId(): ?int;

    public function setScopeId(int $scopeId): void
    {
    }

    protected function dispatchScopeIdChangeEvent()
    {
        $event = new ConfigManagerScopeIdUpdateEvent();
        $this->eventDispatcher->dispatch($event, ConfigManagerScopeIdUpdateEvent::EVENT_NAME);
    }

    public function getScopeInfo(): string
    {
        return '';
    }

    public function getScopeIdFromEntity(object $entity): ?int
    {
        if ($this->isSupportedScopeEntity($entity)) {
            return $this->getScopeEntityIdValue($entity);
        }

        // Must be null because we should not return any scope id if the entity is not supported as a scope entity.
        return null;
    }

    /**
     * Find scope id by provided entity object
     */
    public function setScopeIdFromEntity(?object $entity): void
    {
        if ($entity) {
            $scopeId = $this->getScopeIdFromEntity($entity);

            if ($scopeId) {
                $this->setScopeId($scopeId);
            }
        }
    }

    protected function isSupportedScopeEntity(object $entity): bool
    {
        return false;
    }

    protected function getScopeEntityIdValue(object $entity): mixed
    {
        return null;
    }

    /**
     * Loads settings from a database
     */
    protected function loadStoredSettings(?int $entityId): array
    {
        $config = $this->doctrine->getManagerForClass(Config::class)
            ->getRepository(Config::class)
            ->findByEntity($this->getScopedEntityName(), $entityId);

        if (null === $config) {
            return [];
        }

        return $this->normalizeSettings(SettingsConverter::convertToSettings($config));
    }

    protected function normalizeSettings(array $settings): array
    {
        $configFields = $this->configBag->getConfig()['fields'];
        foreach ($settings as $section => $sectionSettings) {
            foreach ($sectionSettings as $key => $setting) {
                $settingPath = sprintf('%s.%s', $section, $key);
                if (empty($configFields[$settingPath])
                    || $setting['value'] === null
                    || empty($configFields[$settingPath]['data_type'])
                ) {
                    continue;
                }

                $normalizedValue = $this->normalizeSettingValue(
                    $configFields[$settingPath]['data_type'],
                    $setting['value']
                );

                if ($normalizedValue !== null) {
                    $settings[$section][$key]['value'] = $normalizedValue;
                }
            }
        }

        return $settings;
    }

    protected function normalizeSettingValue(string $dataType, mixed $value): mixed
    {
        switch ($dataType) {
            case 'integer':
                return (integer) $value;
            case 'decimal':
                return (float) $value;
            case 'boolean':
                return (boolean) $value;
            default:
                return null;
        }
    }

    protected function getCacheKey(string $entity, ?int $entityId): string
    {
        return UniversalCacheKeyGenerator::normalizeCacheKey($entity . '_' . $entityId);
    }

    public function resolveIdentifier(object|int|null $identifier): ?int
    {
        if (\is_object($identifier)) {
            return $this->getScopeIdFromEntity($identifier);
        }

        return $identifier ?? $this->getScopeId();
    }

    protected function resetCache(): void
    {
        $this->cache->clear();
    }
}
