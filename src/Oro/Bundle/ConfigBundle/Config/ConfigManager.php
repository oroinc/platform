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
    const SECTION_VIEW_SEPARATOR = '___';
    const SECTION_MODEL_SEPARATOR = '.';

    const VALUE_KEY = 'value';
    const SCOPE_KEY = 'scope';
    const USE_PARENT_SCOPE_VALUE_KEY = 'use_parent_scope_value';

    /** @var array Settings array, initiated with global application settings */
    protected $settings;

    /** @var AbstractScopeManager[] */
    protected $managers;

    /** @var AbstractScopeManager[] */
    protected $defaultManagers;

    /** @var string */
    protected $scope;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var MemoryCache */
    private $memoryCache;

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

    /**
     * @return int
     */
    public function getScopeId()
    {
        return $this->getScopeManager()->getScopeId();
    }

    /**
     * @param object $entity
     */
    public function setScopeIdFromEntity($entity)
    {
        $this->getScopeManager()->setScopeIdFromEntity($entity);
    }

    /**
     * @param int $scopeId
     */
    public function setScopeId($scopeId)
    {
        $this->getScopeManager()->setScopeId($scopeId);
    }

    /**
     * @return string
     */
    public function getScopeEntityName()
    {
        return $this->getScopeManager()->getScopedEntityName();
    }

    /**
     * @return string
     */
    public function getScopeInfo()
    {
        return $this->getScopeManager()->getScopeInfo();
    }

    /**
     * Get setting value
     *
     * @param string $name Setting name, for example "oro_user.level"
     * @param bool $default
     * @param bool $full
     * @param null|int|object $scopeIdentifier
     *
     * @return mixed
     */
    public function get($name, $default = false, $full = false, $scopeIdentifier = null)
    {
        $cacheKey = $this->getCacheKey($name, $default, $full, $scopeIdentifier);
        if ($this->memoryCache->has($cacheKey)) {
            return $this->memoryCache->get($cacheKey);
        }

        $value = $this->getValue($name, $default, $full, $scopeIdentifier);
        $this->memoryCache->set($cacheKey, $value);

        return $value;
    }

    /**
     * Get settings for given entities.
     *
     * @param string $name
     * @param array|int[]|object[] $scopeIdentifiers
     * @param bool $default
     * @param bool $full
     * @return array
     */
    public function getValues($name, array $scopeIdentifiers, $default = false, $full = false)
    {
        $result = [];
        foreach ($scopeIdentifiers as $scopeIdentifier) {
            $result[$this->resolveIdentifier($scopeIdentifier)] = $this->get($name, $default, $full, $scopeIdentifier);
        }

        return $result;
    }

    /**
     * Get Additional Info of Config Value
     *
     * @param $name
     * @param null|int|object $scopeIdentifier
     *
     * @return array
     */
    public function getInfo($name, $scopeIdentifier = null)
    {
        $createdValues = [];
        $updatedValues = [];

        $createdValue = $updatedValue = null;
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
            if (count($createdValues) > 0) {
                $createdValue = min($createdValues);
            }
            if (count($updatedValues) > 0) {
                $updatedValue = max($updatedValues);
            }
        }

        return ['createdAt' => $createdValue, 'updatedAt' => $updatedValue];
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
        $this->getScopeManager()->set($name, $value, $scopeIdentifier);

        $this->resetMemoryCache();
    }

    /**
     * Reset setting value to default. To save changes in a database you need to call flush method
     *
     * @param string $name Setting name, for example "oro_user.level"
     * @param null|int|object $scopeIdentifier
     */
    public function reset($name, $scopeIdentifier = null)
    {
        $this->getScopeManager()->reset($name, $scopeIdentifier);

        $this->resetMemoryCache();
    }

    /**
     * Removes scope settings. To save changes in a database, a flush method should be called.
     *
     * @param int|object $scopeIdentifier
     */
    public function deleteScope($scopeIdentifier): void
    {
        $this->getScopeManager()->deleteScope($scopeIdentifier);

        $this->resetMemoryCache();
    }

    /**
     * Save changes made with set or reset methods in a database
     *
     * @param null|int|object $scopeIdentifier
     */
    public function flush($scopeIdentifier = null)
    {
        $identifiers = null === $scopeIdentifier
            ? $this->getScopeManager()->getChangedScopeIdentifiers()
            : [$scopeIdentifier];
        foreach ($identifiers as $identifier) {
            $this->save($this->getScopeManager()->getChanges($identifier), $identifier);
        }
    }

    /**
     * Save settings
     *
     * @param array $settings
     * @param null|int|object $scopeIdentifier
     *
     * @return ConfigChangeSet
     */
    public function save($settings, $scopeIdentifier = null)
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

        $changeSet = new ConfigChangeSet($this->buildChangeSet($updated, $removed, $oldValues));
        $event = new ConfigUpdateEvent($changeSet, $this->scope, $this->resolveIdentifier($scopeIdentifier));
        $this->eventDispatcher->dispatch($event, ConfigUpdateEvent::EVENT_NAME);

        return $changeSet;
    }

    protected function dispatchConfigSettingsUpdateEvent(string $eventName, array $settings): array
    {
        $event = new ConfigSettingsUpdateEvent($this, $settings);
        $this->eventDispatcher->dispatch($event, $eventName);

        return $event->getSettings();
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
        $settings = $this->normalizeSettings($settings);

        return $this->getScopeManager()->calculateChangeSet($settings, $scopeIdentifier);
    }

    /**
     * Reload settings data
     * @param null|int|object $scopeIdentifier
     */
    public function reload($scopeIdentifier = null)
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
     * Get the defaults defined in the settings bag
     *
     * @param string $name Config key name
     * @param bool $full
     *
     * @return mixed|null
     */
    public function getSettingsDefaults($name, $full = false)
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
     * Cascade merge array value with parent scopes.
     * Fill the missing sub-values with those defined in the parent scopes and return the result.
     * E.g. 'config.user_scope'=>['a'=>null, 'b'=>1] and 'config.system_scope' => ['a'=>1, 'b'=>2, 'c'=>3]
     * will result to 'config.users_cope'=> ['a'=>null, 'b'=>1, 'c'=>3]
     * Return unchanged if config option is not an array
     *
     * @param mixed $value
     * @param string $name Config name
     * @param bool $full
     * @param null|int|object $scopeIdentifier
     *
     * @return mixed
     */
    public function getMergedWithParentValue($value, $name, $full = false, $scopeIdentifier = null)
    {
        if (!$this->isArrayValue($value, $full)) {
            return $value;
        }

        // get the value part only (if full)
        $currentValue = (array) $this->getPlainValue($value, $full);

        // merge missing sub-values with those defined in the parent scopes
        $managers = $this->getScopeManagersToGetValue(false);
        foreach ($managers as $scopeName => $manager) {
            $scopeValue = $manager->getSettingValue($name, $full, $scopeIdentifier);
            $currentValue = array_merge((array) $this->getPlainValue($scopeValue, $full), $currentValue);
        }

        return $this->updateWithPlainValue($value, $currentValue, $full);
    }

    /**
     * @return AbstractScopeManager
     */
    protected function getScopeManager()
    {
        return $this->managers[$this->scope];
    }

    /**
     * @param string $name
     * @param bool $default
     * @param bool $full
     * @param null|int|object $scopeIdentifier
     * @param bool $skipChanges
     *
     * @return mixed
     */
    protected function getValue($name, $default = false, $full = false, $scopeIdentifier = null, $skipChanges = false)
    {
        $scopeId = $this->resolveIdentifier($scopeIdentifier);
        $managers = $this->getScopeManagersToGetValue($default);
        $settingValue = null;
        foreach ($managers as $scopeName => $manager) {
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

        $value = $settingValue;
        if ($settingValue !== null && !$full) {
            $value = $settingValue[self::VALUE_KEY];
        }

        $event = new ConfigGetEvent($this, $name, $value, $full, $scopeId);
        $this->eventDispatcher->dispatch($event, ConfigGetEvent::NAME);
        $this->eventDispatcher->dispatch($event, sprintf('%s.%s', ConfigGetEvent::NAME, $name));

        $value = $event->getValue();

        if (null === $value && $settingValue === null) {
            return $this->getSettingsDefaults($name, $full);
        }

        return $value;
    }

    /**
     * @param string $name
     * @param bool $default
     * @param bool $full
     * @param null|int|object $scopeIdentifier
     * @param bool $skipChanges
     *
     * @return string
     */
    private function getCacheKey(
        string $name,
        bool $default = false,
        bool $full = false,
        $scopeIdentifier = null,
        bool $skipChanges = false
    ): string {
        if (is_object($scopeIdentifier)) {
            $managers = $this->getScopeManagersToGetValue($default);
            $scopedEntityName = '';
            $resolvedScopeId = null;
            foreach ($managers as $manager) {
                if ($resolvedScopeId = $manager->getScopeIdFromEntity($scopeIdentifier)) {
                    $scopedEntityName = $manager->getScopedEntityName();
                    break;
                }
            }
        } else {
            $scopedEntityName = $this->getScopeEntityName();
            $resolvedScopeId = $this->resolveIdentifier($scopeIdentifier);
        }

        return sprintf(
            '%s|%s|%d|%s|%d|%d|%d',
            $this->getScopeEntityName(),
            $scopedEntityName,
            (int)$resolvedScopeId,
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

    /**
     * @param mixed $value
     * @param bool $full
     *
     * @return bool
     */
    protected function isArrayValue($value, $full)
    {
        if ($full) {
            return is_array($value[self::VALUE_KEY]);
        }

        return is_array($value);
    }

    /**
     * @param mixed $value
     * @param bool $full
     *
     * @return mixed
     */
    protected function getPlainValue($value, $full)
    {
        return $full ? $value[self::VALUE_KEY] : $value;
    }

    /**
     * @param mixed $value
     * @param mixed $newValue
     * @param bool $full
     *
     * @return mixed
     */
    protected function updateWithPlainValue($value, $newValue, $full)
    {
        if (!$full) {
            return $newValue;
        }

        $value[self::VALUE_KEY] = $newValue;

        return $value;
    }

    /**
     * @param bool $default
     *
     * @return AbstractScopeManager[]
     */
    protected function getScopeManagersToGetValue($default)
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

    /**
     * @param array $settings
     *
     * @return array
     */
    protected function normalizeSettings($settings)
    {
        // normalize names and remove unknown settings
        $normalizedSettings = [];
        if (is_array($settings)) {
            foreach ($settings as $name => $value) {
                [$section, $key] = explode(
                    self::SECTION_MODEL_SEPARATOR,
                    str_replace(self::SECTION_VIEW_SEPARATOR, self::SECTION_MODEL_SEPARATOR, $name)
                );
                if (!empty($this->settings[$section][$key])) {
                    $normalizedSettings[$section . self::SECTION_MODEL_SEPARATOR . $key] = $value;
                }
            }
        }

        return $normalizedSettings;
    }

    /**
     * @param array $updated
     * @param array $removed
     * @param array $oldValues
     * @param null|int|object $scopeIdentifier
     *
     * @return array
     */
    protected function buildChangeSet(array $updated, array $removed, array $oldValues, $scopeIdentifier = null)
    {
        $changeSet = [];
        foreach ($updated as $name => $value) {
            $oldValue = isset($oldValues[$name]) ? $oldValues[$name] : null;
            if ($oldValue != $value) {
                $changeSet[$name] = ['old' => $oldValue, 'new' => $value];
            }
        }
        foreach ($removed as $name) {
            $oldValue = isset($oldValues[$name]) ? $oldValues[$name] : null;
            $value    = $this->getValue($name, true, false, $scopeIdentifier);
            if ($oldValue != $value) {
                $changeSet[$name] = ['old' => $oldValue, 'new' => $value];
            }
        }

        return $changeSet;
    }

    /**
     * @param int|null|object $scopeIdentifier
     * @return int|null
     */
    protected function resolveIdentifier($scopeIdentifier)
    {
        foreach ($this->getScopeManagersToGetValue(false) as $scopeManager) {
            $identifier = $scopeManager->resolveIdentifier($scopeIdentifier);
            if ($identifier !== null) {
                return $identifier;
            }
        }

        return null;
    }
}
