<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;

/**
 * Transforms user ID to User entity and vise versa for a specified configuration option.
 * When there is no user ID in the configuration option value, a default user entity is used.
 */
class DefaultUserSystemConfigListener
{
    private DefaultUserProvider $defaultUserProvider;
    private ManagerRegistry $doctrine;
    private string $configKey;

    public function __construct(
        DefaultUserProvider $defaultUserProvider,
        ManagerRegistry $doctrine,
        string $configKey
    ) {
        $this->defaultUserProvider = $defaultUserProvider;
        $this->doctrine = $doctrine;
        $this->configKey = $configKey;
    }

    public function onFormPreSetData(ConfigSettingsUpdateEvent $event): void
    {
        /** @var string $settingsKey */
        $settingsKey = str_replace(
            ConfigManager::SECTION_MODEL_SEPARATOR,
            ConfigManager::SECTION_VIEW_SEPARATOR,
            $this->configKey
        );
        $settings = $event->getSettings();
        $user = null;
        if (isset($settings[$settingsKey]['value'])) {
            $user = $this->findUser($settings[$settingsKey]['value']);
        }
        if (null === $user) {
            $user = $this->defaultUserProvider->getDefaultUser($this->configKey);
        }
        $settings[$settingsKey]['value'] = $user;
        $event->setSettings($settings);
    }

    public function onSettingsSaveBefore(ConfigSettingsUpdateEvent $event): void
    {
        $settings = $event->getSettings();
        if (isset($settings[$this->configKey]['value'])) {
            $value = $settings[$this->configKey]['value'];
            if ($value instanceof User) {
                $settings[$this->configKey]['value'] = $value->getId();
                $event->setSettings($settings);
            }
        }
    }

    private function findUser(int $id): ?User
    {
        return $this->doctrine->getManagerForClass(User::class)
            ->find(User::class, $id);
    }
}
