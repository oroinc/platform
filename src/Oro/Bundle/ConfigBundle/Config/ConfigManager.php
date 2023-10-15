<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Oro\Bundle\CacheBundle\Provider\MemoryCache;
use Oro\Bundle\ConfigBundle\Event\ConfigGetEvent;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ConfigBundle\Provider\Value\ValueProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Configuration manager.
 * Contains chain of scope managers, get/set config values with a respect to fallback.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ConfigManager
{
    public const SECTION_VIEW_SEPARATOR = '___';
    public const SECTION_MODEL_SEPARATOR = '.';

    public const VALUE_KEY = 'value';
    public const SCOPE_KEY = 'scope';
    public const USE_PARENT_SCOPE_VALUE_KEY = 'use_parent_scope_value';

    private string $scope;
    /** @var array Settings array, initiated with global application settings */
    private array $settings;
    private EventDispatcherInterface $eventDispatcher;
    private MemoryCache $memoryCache;
    /** @var AbstractScopeManager[] [scope name => scope manager, ...] */
    private array $managers = [];
    /** @var AbstractScopeManager[]|null [scope name => scope manager, ...] */
    private ?array $defaultManagers = null;

    public function __construct(
        string $scope,
        ConfigDefinitionImmutableBag $configDefinition,
        EventDispatcherInterface $eventDispatcher,
        MemoryCache $memoryCache
    ) {
        $this->scope = $scope;
        $this->settings = $configDefinition->all();
        $this->eventDispatcher = $eventDispatcher;
        $this->memoryCache = $memoryCache;
    }

    public function addManager(string $scope, AbstractScopeManager $manager): void
    {
        $this->managers[$scope] = $manager;
    }

    public function getScopeId(): int
    {
        return $this->getScopeManager()->getScopeId();
    }

    public function setScopeId(?int $scopeId): void
    {
        $this->getScopeManager()->setScopeId($scopeId);
    }

    public function setScopeIdFromEntity(object $entity): void
    {
        $this->getScopeManager()->setScopeIdFromEntity($entity);
    }

    public function getScopeEntityName(): string
    {
        return $this->getScopeManager()->getScopedEntityName();
    }

    public function getScopeInfo(): string
    {
        return $this->getScopeManager()->getScopeInfo();
    }

    /**
     * Gets a configuration option value.
     */
    public function get(
        string $name,
        bool $default = false,
        bool $full = false,
        object|int|null $scopeIdentifier = null
    ): mixed {
        $cacheKey = $this->getCacheKey($name, $default, $full, $scopeIdentifier);
        if ($this->memoryCache->has($cacheKey)) {
            return $this->memoryCache->get($cacheKey);
        }

        $value = $this->getValue($name, $default, $full, $scopeIdentifier);
        $this->memoryCache->set($cacheKey, $value);

        return $value;
    }

    /**
     * Gets values for the given scopes for a configuration option.
     */
    public function getValues(string $name, array $scopeIdentifiers, bool $default = false, bool $full = false): array
    {
        $result = [];
        foreach ($scopeIdentifiers as $scopeIdentifier) {
            $result[$this->resolveIdentifier($scopeIdentifier)] = $this->get($name, $default, $full, $scopeIdentifier);
        }

        return $result;
    }

    /**
     * Gets an additional info for a configuration option.
     */
    public function getInfo(string $name, object|int|null $scopeIdentifier = null): array
    {
        $createdValues = [];
        $updatedValues = [];
        $createdValue = null;
        $updatedValue = null;
        $valueWasFind = false;
        foreach ($this->managers as $manager) {
            [$created, $updated, $isNullValue] = $manager->getInfo($name, $scopeIdentifier);
            if (!$isNullValue) {
                $createdValue = $created;
                $updatedValue = $updated;
                $valueWasFind = true;
                break;
            }
            if ($created) {
                $createdValues[] = $created;
            }
            if ($updated) {
                $updatedValues[] = $updated;
            }
        }
        if (!$valueWasFind) {
            if ($createdValues) {
                $createdValue = min($createdValues);
            }
            if ($updatedValues) {
                $updatedValue = max($updatedValues);
            }
        }

        return ['createdAt' => $createdValue, 'updatedAt' => $updatedValue];
    }

    /**
     * Sets a configuration option value.
     * To save changes to the database you need to call flush method.
     */
    public function set(string $name, mixed $value, object|int|null $scopeIdentifier = null): void
    {
        $this->getScopeManager()->set($name, $value, $scopeIdentifier);

        $this->resetMemoryCache();
    }

    /**
     * Resets configuration option value to its default value.
     * To save changes to the database you need to call flush method.
     */
    public function reset(string $name, object|int|null $scopeIdentifier = null): void
    {
        $this->getScopeManager()->reset($name, $scopeIdentifier);

        $this->resetMemoryCache();
    }

    /**
     * Removes scope settings.
     * To save changes to the database, a flush method should be called.
     */
    public function deleteScope(object|int $scopeIdentifier): void
    {
        $this->getScopeManager()->deleteScope($scopeIdentifier);

        $this->resetMemoryCache();
    }

    /**
     * Saves changes made with set or reset methods to the database.
     */
    public function flush(object|int|null $scopeIdentifier = null): void
    {
        $identifiers = null === $scopeIdentifier
            ? $this->getScopeManager()->getChangedScopeIdentifiers()
            : [$scopeIdentifier];
        foreach ($identifiers as $identifier) {
            $this->save($this->getScopeManager()->getChanges($identifier), $identifier);
        }
    }

    /**
     * Saves settings.
     */
    public function save(array $settings, object|int|null $scopeIdentifier = null): ConfigChangeSet
    {
        $settings = $this->normalizeSettings($settings);
        if (empty($settings)) {
            return new ConfigChangeSet([]);
        }

        $oldValues = [];
        foreach ($settings as $name => $fieldSettings) {
            $oldValues[$name] = $this->getValue($name, false, false, $scopeIdentifier, true);
            $settings[$name] = $this->dispatchConfigSettingsUpdateEvent(
                sprintf('%s.%s', ConfigSettingsUpdateEvent::BEFORE_SAVE, $name),
                $fieldSettings
            );
        }

        $settings = $this->dispatchConfigSettingsUpdateEvent(ConfigSettingsUpdateEvent::BEFORE_SAVE, $settings);

        [$updated, $removed] = $this->getScopeManager()->save($settings, $scopeIdentifier);

        $this->resetMemoryCache();

        $event = new ConfigUpdateEvent(
            $this->buildChangeSet($updated, $removed, $oldValues),
            $this->scope,
            $this->resolveIdentifier($scopeIdentifier)
        );
        $this->eventDispatcher->dispatch($event, ConfigUpdateEvent::EVENT_NAME);

        return new ConfigChangeSet($event->getChangeSet());
    }

    /**
     * Calculates and returns config change set.
     * Does not modify anything, so even if you call flush after calculating you will not persist any changes.
     *
     * @param array           $settings
     * @param object|int|null $scopeIdentifier
     *
     * @return array [[updated name => value, ...], [removed name, ...]]
     */
    public function calculateChangeSet(array $settings, object|int|null $scopeIdentifier = null): array
    {
        $settings = $this->normalizeSettings($settings);

        return $this->getScopeManager()->calculateChangeSet($settings, $scopeIdentifier);
    }

    /**
     * Reloads settings data.
     */
    public function reload(object|int|null $scopeIdentifier = null): void
    {
        $this->getScopeManager()->reload($scopeIdentifier);

        $this->resetMemoryCache();
    }

    public function getSettingsByForm(FormInterface $form): array
    {
        $settings = [];

        /** @var FormInterface $child */
        foreach ($form as $child) {
            $name = $child->getName();
            $key = str_replace(self::SECTION_VIEW_SEPARATOR, self::SECTION_MODEL_SEPARATOR, $name);
            $settings[$name] = $this->get($key, false, true);
            if (!isset($settings[$name][self::USE_PARENT_SCOPE_VALUE_KEY])) {
                $settings[$name][self::USE_PARENT_SCOPE_VALUE_KEY] = true;
            }
        }

        return $this->dispatchConfigSettingsUpdateEvent(ConfigSettingsUpdateEvent::FORM_PRESET, $settings);
    }

    /**
     * Gets the defaults defined in the settings bag.
     */
    public function getSettingsDefaults(string $name, bool $full = false): mixed
    {
        [$section, $key] = explode(self::SECTION_MODEL_SEPARATOR, $name);

        if (empty($this->settings[$section][$key])) {
            return null;
        }

        $setting = $this->settings[$section][$key];

        if ($setting[self::VALUE_KEY] instanceof ValueProviderInterface) {
            $setting[self::VALUE_KEY] = $setting[self::VALUE_KEY]->getValue();
            // replace provider with value that it returns
            $this->settings[$section][$key][self::VALUE_KEY] = $setting[self::VALUE_KEY];
        }

        if (!$full) {
            return $setting[self::VALUE_KEY];
        }

        return $setting;
    }

    /**
     * Cascade merges array value with parent scopes.
     * Fills the missing sub-values with those defined in the parent scopes and return the result.
     * E.g. 'config.user_scope'=>['a'=>null, 'b'=>1] and 'config.system_scope' => ['a'=>1, 'b'=>2, 'c'=>3]
     * will result to 'config.users_cope'=> ['a'=>null, 'b'=>1, 'c'=>3]
     * Returns unchanged if config option is not an array
     */
    public function getMergedWithParentValue(
        mixed $value,
        string $name,
        bool $full = false,
        object|int|null $scopeIdentifier = null
    ): mixed {
        $plainValue = $this->getPlainValue($value, $full);
        if (!\is_array($plainValue)) {
            return $value;
        }

        // merge missing sub-values with those defined in the parent scopes
        $plainValues = [$plainValue];
        $managers = $this->getScopeManagersToGetValue(false);
        foreach ($managers as $manager) {
            $val = $this->getPlainValue($manager->getSettingValue($name, $full, $scopeIdentifier), $full);
            if (null !== $val) {
                $plainValues[] = (array)$val;
            }
        }
        $plainValue = array_merge(...array_reverse($plainValues));

        if ($full) {
            $value[self::VALUE_KEY] = $plainValue;
        } else {
            $value = $plainValue;
        }

        return $value;
    }

    private function getScopeManager(): AbstractScopeManager
    {
        return $this->managers[$this->scope];
    }

    private function dispatchConfigSettingsUpdateEvent(string $eventName, array $settings): array
    {
        $event = new ConfigSettingsUpdateEvent($this, $settings);
        $this->eventDispatcher->dispatch($event, $eventName);

        return $event->getSettings();
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getValue(
        string $name,
        bool $default = false,
        bool $full = false,
        object|int|null $scopeIdentifier = null,
        bool $skipChanges = false
    ): mixed {
        $resolvedScope = null;
        $resolvedScopeId = null;
        $settingValue = null;
        $managers = $this->getScopeManagersToGetValue($default);
        foreach ($managers as $scopeName => $manager) {
            if (null === $resolvedScopeId && null !== $scopeIdentifier) {
                if (\is_object($scopeIdentifier)) {
                    $identifier = $manager->getScopeIdFromEntity($scopeIdentifier);
                    if (null !== $identifier) {
                        $resolvedScope = $scopeName;
                        $resolvedScopeId = $identifier;
                    }
                } else {
                    $resolvedScope = $scopeName;
                    $resolvedScopeId = $scopeIdentifier;
                }
            }
            $settingValue = $manager->getSettingValue($name, true, $scopeIdentifier, $skipChanges);
            if (null !== $settingValue) {
                // in case if we get value not from current scope,
                // we should mark value that it was get from another scope
                if ($this->scope !== $scopeName) {
                    $settingValue[self::USE_PARENT_SCOPE_VALUE_KEY] = true;
                }
                break;
            }
        }
        if (null === $resolvedScope) {
            $resolvedScope = $this->scope;
        }
        if (null === $resolvedScopeId) {
            $resolvedScopeId = $this->managers[$resolvedScope]->getScopeId();
        }

        $value = $settingValue;
        if (null !== $settingValue && !$full) {
            $value = $settingValue[self::VALUE_KEY];
        }

        $event = new ConfigGetEvent($this, $name, $value, $full, $resolvedScope, $resolvedScopeId);
        $this->eventDispatcher->dispatch($event, ConfigGetEvent::NAME);
        $this->eventDispatcher->dispatch($event, sprintf('%s.%s', ConfigGetEvent::NAME, $name));

        $value = $event->getValue();

        if (null === $value && null === $settingValue) {
            return $this->getSettingsDefaults($name, $full);
        }

        return $value;
    }

    private function getCacheKey(
        string $name,
        bool $default = false,
        bool $full = false,
        object|int|null $scopeIdentifier = null,
        bool $skipChanges = false
    ): string {
        $scopeManager = $this->getScopeManager();
        $scope = $scopeManager->getScopedEntityName();
        $resolvedScope = null;
        if (\is_object($scopeIdentifier)) {
            $resolvedScopeId = null;
            $managers = $this->getScopeManagersToGetValue($default);
            foreach ($managers as $scopeNam => $manager) {
                $resolvedScopeId = $manager->getScopeIdFromEntity($scopeIdentifier);
                if (null !== $resolvedScopeId) {
                    $resolvedScope = $scopeNam;
                    break;
                }
            }
        } else {
            $resolvedScopeId = $scopeManager->resolveIdentifier($scopeIdentifier);
        }

        return sprintf(
            '%s|%s|%d|%s|%d|%d|%d',
            $scope,
            $resolvedScope,
            $resolvedScopeId ?? 0,
            $name,
            (int)$default,
            (int)$full,
            (int)$skipChanges
        );
    }

    private function resetMemoryCache(): void
    {
        $this->memoryCache->deleteAll();
    }

    private function getPlainValue(mixed $value, bool $full): mixed
    {
        return $full ? $value[self::VALUE_KEY] : $value;
    }

    /**
     * @param bool $default
     *
     * @return AbstractScopeManager[]
     */
    private function getScopeManagersToGetValue(bool $default): array
    {
        if (!$default) {
            return $this->managers;
        }

        if (!$this->defaultManagers) {
            // in case if we need default value - skip the current and more priority scope managers than the current one
            $this->defaultManagers = $this->managers;
            foreach ($this->managers as $scope => $manager) {
                unset($this->defaultManagers[$scope]);
                if ($scope === $this->scope) {
                    break;
                }
            }
        }

        return $this->defaultManagers;
    }

    private function normalizeSettings(array $settings): array
    {
        // normalize names and remove unknown settings
        $normalizedSettings = [];
        foreach ($settings as $name => $value) {
            [$section, $key] = explode(
                self::SECTION_MODEL_SEPARATOR,
                str_replace(self::SECTION_VIEW_SEPARATOR, self::SECTION_MODEL_SEPARATOR, $name)
            );
            if (!empty($this->settings[$section][$key])) {
                $normalizedSettings[$section . self::SECTION_MODEL_SEPARATOR . $key] = $value;
            }
        }

        return $normalizedSettings;
    }

    private function buildChangeSet(
        array $updated,
        array $removed,
        array $oldValues,
        object|int|null $scopeIdentifier = null
    ): array {
        $changeSet = [];
        foreach ($updated as $name => $value) {
            $oldValue = $oldValues[$name] ?? null;
            if ($oldValue != $value) {
                $changeSet[$name] = ['old' => $oldValue, 'new' => $value];
            }
        }
        foreach ($removed as $name) {
            $oldValue = $oldValues[$name] ?? null;
            $value = $this->getValue($name, true, false, $scopeIdentifier);
            if ($oldValue != $value) {
                $changeSet[$name] = ['old' => $oldValue, 'new' => $value];
            }
        }

        return $changeSet;
    }

    private function resolveIdentifier(object|int|null $scopeIdentifier): int
    {
        $managers = $this->getScopeManagersToGetValue(false);
        foreach ($managers as $scopeManager) {
            $identifier = $scopeManager->resolveIdentifier($scopeIdentifier);
            if (null !== $identifier) {
                return $identifier;
            }
        }

        return 0;
    }
}
