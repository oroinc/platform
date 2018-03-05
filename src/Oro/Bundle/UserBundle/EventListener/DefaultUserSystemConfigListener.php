<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
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

    /** @var DoctrineHelper*/
    private $doctrineHelper;

    /**
     * @param DefaultUserProvider $defaultUserProvider
     * @param DoctrineHelper      $doctrineHelper
     */
    public function __construct(DefaultUserProvider $defaultUserProvider, DoctrineHelper $doctrineHelper)
    {
        $this->defaultUserProvider = $defaultUserProvider;
        $this->doctrineHelper = $doctrineHelper;
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

        $owner = null;
        if (isset($settings[$settingsKey]['value'])) {
            $owner = $this->getUserById($settings[$settingsKey]['value']);
        }

        if (!$owner) {
            $owner = $this->defaultUserProvider->getDefaultUser($this->alias, $this->configKey);
        }

        $settings[$settingsKey]['value'] = $owner;

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

    /**
     * @param int $userId
     *
     * @return User|null
     */
    private function getUserById(int $userId)
    {
        return $this->doctrineHelper
            ->getEntityRepositoryForClass(User::class)
            ->findOneBy(['id' => $userId]);
    }
}
