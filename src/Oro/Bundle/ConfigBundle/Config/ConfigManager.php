<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Symfony\Component\Form\FormInterface;

class ConfigManager
{
    const SECTION_VIEW_SEPARATOR  = '___';
    const SECTION_MODEL_SEPARATOR = '.';

    /** @var array Settings array, initiated with global application settings */
    protected $settings;

    /** @var array */
    protected $managers;

    /** @var string */
    protected $scopeName;

    /**
     * @param ConfigDefinitionImmutableBag $configDefinition
     */
    public function __construct(
        ConfigDefinitionImmutableBag $configDefinition
    ) {
        $this->settings = $configDefinition->all();
    }

    /**
     * @param string               $scopeName
     * @param AbstractScopeManager $manager
     */
    public function addManager($scopeName, $manager)
    {
        $this->managers[$scopeName] = $manager;
    }

    /**
     * @param string $scopeName
     */
    public function setScopeName($scopeName)
    {
        $this->scopeName = $scopeName;
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
        $managers = array_reverse($this->managers);

        //in case if we need default value - unset current scope manager
        if ($default) {
            unset($managers[$this->scopeName]);
        }
        $value = null;
        foreach ($managers as $scopeName => $manager) {
            /** @var $manager AbstractScopeManager */
            $value = $manager->getSettingValue($name, $full);

            if (!is_null($value)) {
                if (is_array($value)
                    && ($scopeName !== $this->scopeName
                        || (isset($value['scope']) && $value['scope'] !== $manager->getScopedEntityName())
                    )
                ) {
                    $value['use_parent_scope_value'] = true;
                }
                break;
            }
        }

        list($section, $key) = explode(self::SECTION_MODEL_SEPARATOR, $name);
        if (is_null($value) && !empty($this->settings[$section][$key])) {
            $setting = $this->settings[$section][$key];
            return is_array($setting) && array_key_exists('value', $setting) && !$full ? $setting['value'] : $setting;
        }

        return $value;
    }

    /**
     * @param int    $scopeId
     * @param string $scope
     *
     * @return $this
     */
    public function setScopeId($scopeId = null, $scope = null)
    {
        if (is_null($scope)) {
            $scope = $this->scopeName;
        }

        $this->managers[$scope]->setScopeId($scopeId);

        return $this;
    }

    /**
     * Get Additional Info of Config Value
     *
     * @param $name
     * @return array
     */
    public function getInfo($name)
    {
        return $this->getScopeManager()->getInfo($name);
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
    }

    /**
     * Reset setting value to default. To save changes in a database you need to call flush method
     *
     * @param string $name Setting name, for example "oro_user.level"
     */
    public function reset($name)
    {
        $this->getScopeManager()->reset($name);
    }

    /**
     * Save changes made with set or reset methods in a database
     */
    public function flush()
    {
        $this->getScopeManager()->flush();
    }

    /**
     * Save settings with fallback to global scope (default)
     */
    public function save($newSettings)
    {
        $this->getScopeManager()->save($newSettings);
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
        return $this->getScopeManager()->calculateChangeSet($newSettings);
    }

    /**
     * @param string $entity
     * @param int    $entityId
     *
     * @return bool
     */
    public function loadStoredSettings($entity, $entityId)
    {
        return $this->getScopeManager()->loadStoredSettings($entity, $entityId);
    }

    /**
     * Reload settings data
     */
    public function reload()
    {
        $this->getScopeManager()->reload();
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
            $key = str_replace(
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
     * @return AbstractScopeManager
     */
    protected function getScopeManager()
    {
        return $this->managers[$this->scopeName];
    }
}
