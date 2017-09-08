<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;

class DefaultUserSystemConfigListener
{
    /** @var DefaultUserProvider */
    private $defaultUserProvider;

    /** @var string */
    private $alias;

    /** @var string */
    private $configKey;

    /**
     * @param DefaultUserProvider $defaultUserProvider
     */
    public function __construct(DefaultUserProvider $defaultUserProvider)
    {
        $this->defaultUserProvider = $defaultUserProvider;
    }

    /**
     * @param string $alias
     *
     * @return DefaultUserSystemConfigListener
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @param string $configKey
     *
     * @return DefaultUserSystemConfigListener
     */
    public function setConfigKey($configKey)
    {
        $this->configKey = $configKey;

        return $this;
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function onFormPreSetData(ConfigSettingsUpdateEvent $event)
    {
        $settingsKey = $this->getSettingsKey();
        $settings = $event->getSettings();

        $settings[$settingsKey]['value'] = $this->defaultUserProvider
            ->getDefaultUser($this->alias, $this->configKey);

        $event->setSettings($settings);
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function onSettingsSaveBefore(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();

        if (!isset($settings['value'])) {
            return;
        }

        if (!is_a($settings['value'], User::class)) {
            return;
        }

        /** @var User $owner */
        $owner = $settings['value'];
        $settings['value'] = $owner->getId();
        $event->setSettings($settings);
    }

    /**
     * @return string
     */
    private function getSettingsKey()
    {
        return TreeUtils::getConfigKey($this->alias, $this->configKey, ConfigManager::SECTION_VIEW_SEPARATOR);
    }
}
