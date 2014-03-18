<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;

class ConfigManager
{
    const SECTION_VIEW_SEPARATOR  = '___';
    const SECTION_MODEL_SEPARATOR = '.';
    const SCOPE_NAME              = 'app';

    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Settings array, initiated with global application settings
     *
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $storedSettings = array();

    /**
     * @var array
     */
    protected $changedSettings = array();

    /**
     *
     * @param EventDispatcherInterface     $eventDispatcher
     * @param ObjectManager                $om
     * @param ConfigDefinitionImmutableBag $configDefinition
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ObjectManager $om,
        ConfigDefinitionImmutableBag $configDefinition
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->om              = $om;
        $this->settings        = $configDefinition->all();
    }

    /**
     * Get setting value
     *
     * @param  string $name Setting name, for example "oro_user.level"
     * @param bool    $default
     * @param bool    $full
     *
     * @return array|string
     */
    public function get($name, $default = false, $full = false)
    {
        $entity   = $this->getScopedEntityName();
        $entityId = $this->getScopeId();
        $this->loadStoredSettings($entity, $entityId);

        $name    = explode(self::SECTION_MODEL_SEPARATOR, $name);
        $section = $name[0];
        $key     = $name[1];

        if ($default) {
            $settings = $this->settings;
        } elseif (isset($this->storedSettings[$entity][$entityId][$section][$key])) {
            $settings = $this->storedSettings[$entity][$entityId];
        } elseif (isset($this->settings[$section][$key])) {
            $settings = $this->settings;
        }

        if (empty($settings[$section][$key])) {
            return null;
        } else {
            $setting = $settings[$section][$key];

            return is_array($setting) && isset($setting['value']) && !$full ? $setting['value'] : $setting;
        }
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

        $changeKey                         = str_replace(
            self::SECTION_MODEL_SEPARATOR,
            self::SECTION_VIEW_SEPARATOR,
            $name
        );
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

        $pair    = explode(self::SECTION_MODEL_SEPARATOR, $name);
        $section = $pair[0];
        $key     = $pair[1];

        unset($this->storedSettings[$entity][$entityId][$section][$key]);

        $changeKey                         = str_replace(
            self::SECTION_MODEL_SEPARATOR,
            self::SECTION_VIEW_SEPARATOR,
            $name
        );
        $this->changedSettings[$changeKey] = ['use_parent_scope_value' => true];
    }

    /**
     * Save changes made with set or reset methods in a database
     */
    public function flush()
    {
        if (!empty($this->changedSettings)) {
            $this->save($this->changedSettings);
            $this->changedSettings = array();
        }
    }

    /**
     * Save settings with fallback to global scope (default)
     */
    public function save($newSettings)
    {
        $repository = $this->om->getRepository('OroConfigBundle:ConfigValue');
        /** @var Config $config */
        $config = $this->om
            ->getRepository('OroConfigBundle:Config')
            ->getByEntity($this->getScopedEntityName(), $this->getScopeId());

        list ($updated, $removed) = $this->calculateChangeSet($newSettings);

        if (!empty($removed)) {
            $repository->removeValues($config, $removed);
        }

        foreach ($updated as $newItemKey => $newItemValue) {
            $newItemKey   = explode(self::SECTION_VIEW_SEPARATOR, $newItemKey);
            $newItemValue = is_array($newItemValue) ? $newItemValue['value'] : $newItemValue;

            /** @var ConfigValue $value */
            $value = $config->getOrCreateValue($newItemKey[0], $newItemKey[1]);
            $value->setValue($newItemValue);

            $config->getValues()->add($value);
        }

        $this->om->persist($config);
        $this->om->flush();

        $event = new ConfigUpdateEvent($this, $updated, $removed);
        $this->eventDispatcher->dispatch(ConfigUpdateEvent::EVENT_NAME, $event);

        $this->reload();
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
        $updated = array();
        $removed = array();
        foreach ($newSettings as $key => $value) {
            $currentValue = $this->get(
                str_replace(
                    self::SECTION_VIEW_SEPARATOR,
                    self::SECTION_MODEL_SEPARATOR,
                    $key
                ),
                false,
                true
            );

            // save only if setting exists and there's no default checkbox checked
            if (!is_null($currentValue) && empty($value['use_parent_scope_value'])) {
                $updated[$key] = $value;
            }

            $valueDefined      = isset($currentValue['use_parent_scope_value'])
                && $currentValue['use_parent_scope_value'] == false;
            $valueStillDefined = isset($value['use_parent_scope_value'])
                && $value['use_parent_scope_value'] == false;

            if ($valueDefined && !$valueStillDefined) {
                $key       = explode(self::SECTION_VIEW_SEPARATOR, $key);
                $removed[] = array($key[0], $key[1]);
            }
        }

        return array($updated, $removed);
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

        // TODO: optimize it
        // merge app settings with scope settings
        if ($entity != static::SCOPE_NAME) {
            $appConfig = $this->om
                ->getRepository('OroConfigBundle:Config')
                ->loadSettings(static::SCOPE_NAME, 0);
            $config    = array_merge($appConfig, $config);
        }

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
     * @param FormInterface $form
     *
     * @return array
     */
    public function getSettingsByForm(FormInterface $form)
    {
        $settings = array();

        foreach ($form as $child) {
            $key                         = str_replace(
                self::SECTION_VIEW_SEPARATOR,
                self::SECTION_MODEL_SEPARATOR,
                $child->getName()
            );
            $settings[$child->getName()] = $this->get($key, false, true);

            $settings[$child->getName()]['use_parent_scope_value']
                = !isset($settings[$child->getName()]['use_parent_scope_value'])
                ? true : $settings[$child->getName()]['use_parent_scope_value'];

        }

        return $settings;
    }

    /**
     * @return null
     */
    public function getScopedEntityName()
    {
        return static::SCOPE_NAME;
    }

    /**
     * @return int
     */
    public function getScopeId()
    {
        return 0;
    }
}
