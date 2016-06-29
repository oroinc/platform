<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ConfigBundle\Event\ConfigGetEvent;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ConfigManager
{
    const SECTION_VIEW_SEPARATOR = '___';
    const SECTION_MODEL_SEPARATOR = '.';

    /** @var array Settings array, initiated with global application settings */
    protected $settings;

    /** @var ConfigValueBag */
    protected $localCache;

    /** @var AbstractScopeManager[] */
    protected $managers;

    /** @var string */
    protected $scope;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param string                       $scope
     * @param ConfigDefinitionImmutableBag $configDefinition
     * @param EventDispatcherInterface     $eventDispatcher
     * @param ConfigValueBag               $valueBag
     */
    public function __construct(
        $scope,
        ConfigDefinitionImmutableBag $configDefinition,
        EventDispatcherInterface $eventDispatcher,
        ConfigValueBag $valueBag
    ) {
        $this->scope           = $scope;
        $this->settings        = $configDefinition->all();
        $this->eventDispatcher = $eventDispatcher;
        $this->localCache      = $valueBag;
    }

    /**
     * @param string               $scope
     * @param AbstractScopeManager $manager
     */
    public function addManager($scope, $manager)
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
     * @return int
     */
    public function setScopeIdFromEntity($entity)
    {
        return $this->getScopeManager()->setScopeIdFromEntity($entity);
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
    public function getScopeInfo()
    {
        return $this->getScopeManager()->getScopeInfo();
    }

    /**
     * Get setting value
     *
     * @param string $name Setting name, for example "oro_user.level"
     * @param bool   $default
     * @param bool   $full
     *
     * @return mixed
     */
    public function get($name, $default = false, $full = false)
    {
        // full and default values are not cached locally
        if ($full || $default) {
            return $this->getValue($name, $default, $full);
        }

        // try to get a value from a local cache
        $scopeId = $this->getScopeId();
        if ($this->localCache->hasValue($this->scope, $scopeId, $name)) {
            return $this->localCache->getValue($this->scope, $scopeId, $name);
        }

        $value = $this->getValue($name, $default, $full);

        // put to a local cache
        $this->localCache->setValue($this->scope, $scopeId, $name, $value);

        return $value;
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
        $createdValues = [];
        $updatedValues = [];

        $createdValue = $updatedValue = null;
        $valueWasFind = false;

        foreach ($this->managers as $manager) {
            list($created, $updated, $isNullValue) = $manager->getInfo($name);
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
            if (!empty($createdValues)) {
                $createdValue = min($createdValues);
            }
            if (!empty($updatedValues)) {
                $updatedValue = max($updatedValues);
            }
        }

        return ['createdAt' => $createdValue, 'updatedAt' => $updatedValue];
    }

    /**
     * Set setting value. To save changes in a database you need to call flush method
     *
     * @param string $name  Setting name, for example "oro_user.level"
     * @param mixed  $value Setting value
     */
    public function set($name, $value)
    {
        $this->getScopeManager()->set($name, $value);

        // put to a local cache
        $this->localCache->setValue($this->scope, $this->getScopeId(), $name, $value);
    }

    /**
     * Reset setting value to default. To save changes in a database you need to call flush method
     *
     * @param string $name Setting name, for example "oro_user.level"
     */
    public function reset($name)
    {
        $this->getScopeManager()->reset($name);

        // remove from a local cache
        $this->localCache->removeValue($this->scope, $this->getScopeId(), $name);
    }

    /**
     * Save changes made with set or reset methods in a database
     */
    public function flush()
    {
        $this->save($this->getScopeManager()->getChanges());
    }

    /**
     * Save settings
     *
     * @param array $settings
     */
    public function save($settings)
    {
        $settings = $this->normalizeSettings($settings);
        if (empty($settings)) {
            return;
        }

        $oldValues = [];
        foreach ($settings as $name => $value) {
            $oldValues[$name] = $this->getValue($name);
        }

        $event = new ConfigSettingsUpdateEvent($this, $settings);
        $this->eventDispatcher->dispatch(ConfigSettingsUpdateEvent::BEFORE_SAVE, $event);

        list($updated, $removed) = $this->getScopeManager()->save($event->getSettings());

        $event = new ConfigUpdateEvent($this->buildChangeSet($updated, $removed, $oldValues));
        $this->eventDispatcher->dispatch(ConfigUpdateEvent::EVENT_NAME, $event);

        // clear a local cache
        $this->localCache->clear();
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
        $settings = $this->normalizeSettings($settings);

        return $this->getScopeManager()->calculateChangeSet($settings);
    }

    /**
     * Reload settings data
     */
    public function reload()
    {
        $this->getScopeManager()->reload();

        // clear a local cache
        $this->localCache->clear();
    }

    /**
     * @param FormInterface $form
     *
     * @return array
     */
    public function getSettingsByForm(FormInterface $form)
    {
        $settings = [];

        /** @var FormInterface $child */
        foreach ($form as $child) {
            $name            = $child->getName();
            $key             = str_replace(self::SECTION_VIEW_SEPARATOR, self::SECTION_MODEL_SEPARATOR, $name);
            $settings[$name] = $this->get($key, false, true);

            if (!isset($settings[$name]['use_parent_scope_value'])) {
                $settings[$name]['use_parent_scope_value'] = true;
            }
        }

        $event = new ConfigSettingsUpdateEvent($this, $settings);
        $this->eventDispatcher->dispatch(ConfigSettingsUpdateEvent::FORM_PRESET, $event);

        return $event->getSettings();
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
     * @param bool   $default
     * @param bool   $full
     *
     * @return mixed
     */
    protected function getValue($name, $default = false, $full = false)
    {
        $value    = null;
        $managers = $this->getScopeManagersToGetValue($default);
        foreach ($managers as $scopeName => $manager) {
            $value = $manager->getSettingValue($name, $full);
            if (null !== $value) {
                // in case if we get value not from current scope,
                // we should mark value that it was get from another scope
                if ($full && $this->scope !== $scopeName) {
                    $value['use_parent_scope_value'] = true;
                }
                break;
            }
        }

        $event = new ConfigGetEvent($this, $name, $value, $full);
        $this->eventDispatcher->dispatch(ConfigGetEvent::NAME, $event);
        $this->eventDispatcher->dispatch(sprintf('%s.%s', ConfigGetEvent::NAME, $name), $event);

        $value = $event->getValue();

        if (null === $value) {
            list($section, $key) = explode(self::SECTION_MODEL_SEPARATOR, $name);
            if (!empty($this->settings[$section][$key])) {
                $setting = $this->settings[$section][$key];
                if (!$full && is_array($setting) && array_key_exists('value', $setting)) {
                    $value = $setting['value'];
                } else {
                    $value = $setting;
                }
            }
        }

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

        // in case if we need default value - skip the current and more priority scope managers than the current one
        $managers = $this->managers;
        foreach ($this->managers as $scope => $manager) {
            unset($managers[$scope]);
            if ($scope === $this->scope) {
                break;
            }
        }

        return $managers;
    }

    /**
     * @param array|null $settings
     *
     * @return array
     */
    protected function normalizeSettings($settings)
    {
        // normalize names and remove unknown settings
        $normalizedSettings = [];
        foreach ($settings as $name => $value) {
            list($section, $key) = explode(
                ConfigManager::SECTION_MODEL_SEPARATOR,
                str_replace(self::SECTION_VIEW_SEPARATOR, self::SECTION_MODEL_SEPARATOR, $name)
            );
            if (!empty($this->settings[$section][$key])) {
                $normalizedSettings[$section . self::SECTION_MODEL_SEPARATOR . $key] = $value;
            }
        }

        return $normalizedSettings;
    }

    /**
     * @param array $updated
     * @param array $removed
     * @param array $oldValues
     *
     * @return array
     */
    protected function buildChangeSet(array $updated, array $removed, array $oldValues)
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
            $value    = $this->getValue($name, true);
            if ($oldValue != $value) {
                $changeSet[$name] = ['old' => $oldValue, 'new' => $value];
            }
        }

        return $changeSet;
    }
}
