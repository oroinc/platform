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
     * @param bool $default
     * @param bool $full
     * @param null|int $entityId
     *
     * @return mixed
     */
    public function get($name, $default = false, $full = false, $entityId = null)
    {
        // full and default values are not cached locally
        if ($full || $default) {
            return $this->getValue($name, $default, $full, $entityId);
        }

        // try to get a value from a local cache
        if (null === $entityId) {
            $entityId = $this->getScopeId();
        }
        if ($this->localCache->hasValue($this->scope, $entityId, $name)) {
            return $this->localCache->getValue($this->scope, $entityId, $name);
        }

        $value = $this->getValue($name, $default, $full, $entityId);

        // put to a local cache
        $this->localCache->setValue($this->scope, $entityId, $name, $value);

        return $value;
    }

    /**
     * Get settings for given entities.
     *
     * @param string $name
     * @param array $entityIds
     * @param bool $default
     * @param bool $full
     * @return array
     */
    public function getValues($name, array $entityIds, $default = false, $full = false)
    {
        $result = [];
        foreach ($entityIds as $entityId) {
            $result[$entityId] = $this->get($name, $default, $full, $entityId);
        }

        return $result;
    }

    /**
     * Get Additional Info of Config Value
     *
     * @param $name
     * @param null|int $entityId
     *
     * @return array
     */
    public function getInfo($name, $entityId = null)
    {
        $createdValues = [];
        $updatedValues = [];

        $createdValue = $updatedValue = null;
        $valueWasFind = false;

        foreach ($this->managers as $manager) {
            list($created, $updated, $isNullValue) = $manager->getInfo($name, $entityId);
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
     * @param null|int $entityId
     */
    public function set($name, $value, $entityId = null)
    {
        if (null === $entityId) {
            $entityId = $this->getScopeId();
        }
        $this->getScopeManager()->set($name, $value, $entityId);

        // put to a local cache
        $this->localCache->setValue($this->scope, $entityId, $name, $value);
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
        $this->getScopeManager()->reset($name, $entityId);

        // remove from a local cache
        $this->localCache->removeValue($this->scope, $entityId, $name);
    }

    /**
     * Save changes made with set or reset methods in a database
     * @param null|null $entityId
     */
    public function flush($entityId = null)
    {
        $this->save($this->getScopeManager()->getChanges($entityId), $entityId);
    }

    /**
     * Save settings
     *
     * @param array $settings
     * @param null|int $entityId
     */
    public function save($settings, $entityId = null)
    {
        $settings = $this->normalizeSettings($settings);
        if (empty($settings)) {
            return;
        }

        $oldValues = [];
        foreach ($settings as $name => $value) {
            $oldValues[$name] = $this->getValue($name, false, false, $entityId);
        }

        $event = new ConfigSettingsUpdateEvent($this, $settings);
        $this->eventDispatcher->dispatch(ConfigSettingsUpdateEvent::BEFORE_SAVE, $event);

        list($updated, $removed) = $this->getScopeManager()->save($event->getSettings(), $entityId);

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
     * @param null|int $entityId
     *
     * @return array [updated,              removed]
     *               [[name => value, ...], [name, ...]]
     */
    public function calculateChangeSet(array $settings, $entityId = null)
    {
        $settings = $this->normalizeSettings($settings);

        return $this->getScopeManager()->calculateChangeSet($settings, $entityId);
    }

    /**
     * Reload settings data
     * @param null|int $entityId
     */
    public function reload($entityId = null)
    {
        $this->getScopeManager()->reload($entityId);

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
     * @param bool $default
     * @param bool $full
     * @param null|int $entityId
     *
     * @return mixed
     */
    protected function getValue($name, $default = false, $full = false, $entityId = null)
    {
        $value    = null;
        if (null === $entityId) {
            $entityId = $this->getScopeId();
        }
        $managers = $this->getScopeManagersToGetValue($default);
        foreach ($managers as $scopeName => $manager) {
            $value = $manager->getSettingValue($name, $full, $entityId);
            if (null !== $value) {
                // in case if we get value not from current scope,
                // we should mark value that it was get from another scope
                if ($full && $this->scope !== $scopeName) {
                    $value['use_parent_scope_value'] = true;
                }
                break;
            }
        }

        return $this->getProcessedValue($name, $full, $value, $entityId);
    }

    /**
     * @param string $name
     * @param bool $full
     * @param mixed $value
     * @param int $scopeId
     * @return mixed
     */
    protected function getProcessedValue($name, $full, $value, $scopeId)
    {
        $event = new ConfigGetEvent($this, $name, $value, $full, $scopeId);
        $this->eventDispatcher->dispatch(ConfigGetEvent::NAME, $event);
        $this->eventDispatcher->dispatch(sprintf('%s.%s', ConfigGetEvent::NAME, $name), $event);

        $value = $event->getValue();

        if (null === $value) {
            list($section, $key) = explode(self::SECTION_MODEL_SEPARATOR, $name);
            if (!empty($this->settings[$section][$key])) {
                $value = $this->settings[$section][$key];
                if (!$full && is_array($value) && array_key_exists('value', $value)) {
                    $value = $value['value'];
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
                list($section, $key) = explode(
                    ConfigManager::SECTION_MODEL_SEPARATOR,
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
     * @param null|null $entityId
     *
     * @return array
     */
    protected function buildChangeSet(array $updated, array $removed, array $oldValues, $entityId = null)
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
            $value    = $this->getValue($name, true, false, $entityId);
            if ($oldValue != $value) {
                $changeSet[$name] = ['old' => $oldValue, 'new' => $value];
            }
        }

        return $changeSet;
    }
}
