<?php

namespace Oro\Bundle\LocaleBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigValueRepository;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;

/**
 * Set default (global value) localization values for available scopes on ConfigUpdateEvent
 * Remove all custom localization settings for available scopes
 */
class LocalizationChangeListener
{
    /** @var ConfigManager */
    private $configManager;

    /** @var ManagerRegistry */
    private $managerRegistry;

    /**
     * @param ConfigManager $configManager
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ConfigManager $configManager, ManagerRegistry $managerRegistry)
    {
        $this->configManager = $configManager;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function onConfigUpdate(ConfigUpdateEvent $event): void
    {
        if (!$event->isChanged('oro_locale.enabled_localizations') || 'global' !== $event->getScope()) {
            return;
        }

        $values = $this->getRepository()
            ->getConfigValues(
                $this->configManager->getScopeEntityName(),
                'oro_locale',
                'default_localization'
            );

        $availableLocalizations = $event->getNewValue('oro_locale.enabled_localizations');
        foreach ($values as $value) {
            if (!in_array($value->getValue(), $availableLocalizations, true)) {
                $recordId = $value->getConfig()->getRecordId();

                $this->configManager->reset('oro_locale.default_localization', $recordId);
                $this->configManager->flush($recordId);
            }
        }
    }

    /**
     * @return ConfigValueRepository
     */
    private function getRepository(): ConfigValueRepository
    {
        return $this->managerRegistry->getManagerForClass(ConfigValue::class)
            ->getRepository(ConfigValue::class);
    }
}
