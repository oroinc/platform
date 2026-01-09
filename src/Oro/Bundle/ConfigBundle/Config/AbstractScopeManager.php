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
    private EventDispatcherInterface $eventDispatcher;
    private ConfigBag $configBag;
    private array $changedSettings = [];

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
     * Gets a configuration option value.
     */
    public function getSettingValue(
        string $name,
        bool $full = false,
        object|int|null $scopeIdentifier = null,
        bool $skipChanges = false
    ): array|string|null {
        $setting = $this->getCachedSetting($this->resolveIdentifier($scopeIdentifier), $name, $skipChanges);
        if (null === $setting) {
            return null;
        }

        $result = null;
        if (
            null !== $setting[ConfigManager::VALUE_KEY]
            || false === $setting[ConfigManager::USE_PARENT_SCOPE_VALUE_KEY]
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
     * Gets an additional info for a configuration option.
     */
    public function getInfo(string $name, object|int|null $scopeIdentifier = null): array
    {
        $createdAt = null;
        $updatedAt = null;
        $isNullValue = true;
        $setting = $this->getCachedSetting($this->resolveIdentifier($scopeIdentifier), $name);
        if (null !== $setting) {
            $isNullValue = false;
            if (\array_key_exists('createdAt', $setting)) {
                $createdAt = $setting['createdAt'];
            }
            if (\array_key_exists('updatedAt', $setting)) {
                $updatedAt = $setting['updatedAt'];
            }
        }

        return [$createdAt, $updatedAt, $isNullValue];
    }

    /**
     * Sets a configuration option value.
     * To save changes to the database you need to call flush method.
     */
    public function set(string $name, mixed $value, object|int|null $scopeIdentifier = null): void
    {
        $entityId = $this->resolveIdentifier($scopeIdentifier);
        [$section, $key] = explode(ConfigManager::SECTION_MODEL_SEPARATOR, $name);
        $settings = $this->normalizeSettings([$section => [$key => [ConfigManager::VALUE_KEY => $value]]]);

        $this->changedSettings[$entityId][$name] = [
            ConfigManager::VALUE_KEY => $settings[$section][$key][ConfigManager::VALUE_KEY],
            ConfigManager::USE_PARENT_SCOPE_VALUE_KEY => false
        ];
    }

    /**
     * Resets configuration option value to its default value.
     * To save changes to the database you need to call flush method.
     */
    public function reset(string $name, object|int|null $scopeIdentifier = null): void
    {
        $entityId = $this->resolveIdentifier($scopeIdentifier);

        $this->cache->delete($this->getCacheKey($this->getScopedEntityName(), $entityId));

        $this->changedSettings[$entityId][$name] = [
            ConfigManager::USE_PARENT_SCOPE_VALUE_KEY => true
        ];
    }

    /**
     * Removes scope settings.
     * To save changes to the database, a flush method should be called.
     */
    public function deleteScope(object|int $scopeIdentifier): void
    {
        $entity = $this->getScopedEntityName();
        $entityId = $this->resolveIdentifier($scopeIdentifier);

        $config = $this->findByEntity($entity, $entityId);
        if (null !== $config) {
            foreach ($config->getValues() as $value) {
                $name = $value->getSection() . ConfigManager::SECTION_MODEL_SEPARATOR . $value->getName();
                $this->changedSettings[$entityId][$name] = [
                    ConfigManager::USE_PARENT_SCOPE_VALUE_KEY => true
                ];
            }
        }

        $this->cache->delete($this->getCacheKey($entity, $entityId));
    }

    public function getChanges(object|int|null $scopeIdentifier = null): array
    {
        $entityId = $this->resolveIdentifier($scopeIdentifier);
        if (\array_key_exists($entityId, $this->changedSettings)) {
            return $this->changedSettings[$entityId];
        }

        return [];
    }

    public function getChangedScopeIdentifiers(): array
    {
        return array_keys($this->changedSettings);
    }

    /**
     * Saves changes made with set or reset methods to the database.
     */
    public function flush(object|int|null $scopeIdentifier = null): void
    {
        $entityId = $this->resolveIdentifier($scopeIdentifier);
        if (!empty($this->changedSettings[$entityId])) {
            $this->save($this->changedSettings[$entityId], $scopeIdentifier);
            $this->changedSettings[$entityId] = [];
        }
    }

    /**
     * Saves settings with fallback to global scope (default).
     */
    public function save(array $settings, object|int|null $scopeIdentifier = null): array
    {
        $entity = $this->getScopedEntityName();
        $entityId = $this->resolveIdentifier($scopeIdentifier);

        $config = $this->findByEntity($entity, $entityId);
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

        $em = $this->doctrine->getManagerForClass(Config::class);
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
     * Calculates and returns config change set.
     * Does not modify anything, so even if you call flush after calculating you will not persist any changes.
     *
     * @return array [[updated name => value, ...], [removed name, ...]]
     */
    public function calculateChangeSet(array $settings, object|int|null $scopeIdentifier = null): array
    {
        $updated = [];
        $removed = [];
        $entityId = $this->resolveIdentifier($scopeIdentifier);
        foreach ($settings as $name => $value) {
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
     * Reloads settings data.
     */
    public function reload(object|int|null $scopeIdentifier = null): void
    {
        $this->resetCache();

        $entityId = $this->resolveIdentifier($scopeIdentifier);
        $cacheKey = $this->getCacheKey($this->getScopedEntityName(), $entityId);

        $settings = $this->loadStoredSettings($entityId);
        $this->cache->delete($cacheKey);
        $this->cache->get($cacheKey, function () use ($settings) {
            return $settings;
        });

        $this->dispatchScopeIdChangeEvent();
    }

    public function getScopeInfo(): string
    {
        return '';
    }

    public function getScopeIdFromEntity(object $entity): ?int
    {
        if (!$this->isSupportedScopeEntity($entity)) {
            // must be null because we should not return any scope ID if the entity is not supported as a scope entity
            return null;
        }

        return $this->getScopeEntityIdValue($entity);
    }

    public function setScopeIdFromEntity(object $entity): void
    {
        $scopeId = $this->getScopeIdFromEntity($entity);
        if ($scopeId) {
            $this->setScopeId($scopeId);
        }
    }

    public function resolveIdentifier(object|int|null $scopeIdentifier): ?int
    {
        if (\is_object($scopeIdentifier)) {
            return $this->getScopeIdFromEntity($scopeIdentifier);
        }

        return $scopeIdentifier ?? $this->getScopeId();
    }

    abstract public function getScopedEntityName(): string;

    abstract public function getScopeId(): int;

    abstract public function setScopeId(?int $scopeId): void;

    abstract protected function isSupportedScopeEntity(object $entity): bool;

    abstract protected function getScopeEntityIdValue(object $entity): int;

    protected function dispatchScopeIdChangeEvent(): void
    {
        $event = new ConfigManagerScopeIdUpdateEvent();
        $this->eventDispatcher->dispatch($event, ConfigManagerScopeIdUpdateEvent::EVENT_NAME);
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
            $keySetting = array_merge($keySetting ?? [], $this->changedSettings[$entityId][$name]);
        }

        return $keySetting;
    }

    /**
     * Loads settings from the database.
     */
    protected function loadStoredSettings(?int $entityId): array
    {
        $config = $this->findByEntity($this->getScopedEntityName(), $entityId);
        if (null === $config) {
            return [];
        }

        return $this->normalizeSettings(SettingsConverter::convertToSettings($config));
    }

    protected function normalizeSettings(array $settings): array
    {
        $config = $this->configBag->getConfig();
        $configFields = $config['fields'];
        foreach ($settings as $section => $sectionSettings) {
            foreach ($sectionSettings as $key => $setting) {
                $settingPath = sprintf('%s.%s', $section, $key);
                if (
                    empty($configFields[$settingPath])
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
        return match ($dataType) {
            'integer' => (int) $value,
            'decimal', 'float' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value
        };
    }

    protected function getCacheKey(string $entity, ?int $entityId): string
    {
        return UniversalCacheKeyGenerator::normalizeCacheKey($entity . '_' . $entityId);
    }

    protected function resetCache(): void
    {
        $this->cache->clear();
    }

    private function findByEntity(string $scope, ?int $scopeId): ?Config
    {
        if (null === $scopeId) {
            return null;
        }

        return $this->doctrine->getManagerForClass(Config::class)
            ->getRepository(Config::class)
            ->findByEntity($scope, $scopeId);
    }
}
