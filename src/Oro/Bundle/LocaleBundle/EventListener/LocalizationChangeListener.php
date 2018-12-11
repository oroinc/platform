<?php

namespace Oro\Bundle\LocaleBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigValueRepository;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;

/**
 * Set default (global value) localization values for users, organizations and websites on ConfigUpdateEvent
 * Remove all custom localization settings for users, organizations and websites
 */
class LocalizationChangeListener
{
    private const SCOPE_KEY = 'scope';
    private const MANAGER_KEY = 'manager';

    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var array */
    private $configManagers;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
        $this->configManagers = [];
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function onConfigUpdate(ConfigUpdateEvent $event)
    {
        if (!$event->isChanged('oro_locale.default_localization') || 'global' !== $event->getScope()) {
            return;
        }

        /** @var ConfigValueRepository $repository */
        $repository = $this->managerRegistry->getManagerForClass(ConfigValue::class)->getRepository(ConfigValue::class);

        $configManagers = $this->getAvailableConfigManagers();

        foreach ($configManagers as $configManager) {
            $values = $repository->getConfigValues(
                $configManager->getScopeEntityName(),
                'oro_locale',
                'default_localization'
            );

            foreach ($values as $value) {
                $configManager->reset('oro_locale.default_localization', $value->getConfig()->getRecordId());
                $configManager->flush($value->getConfig()->getRecordId());
            }
        }
    }

    /**
     * @param string $scope
     * @param ConfigManager $manager
     */
    public function addConfigManager($scope, ConfigManager $manager)
    {
        $this->configManagers[] = [self::SCOPE_KEY => $scope, self::MANAGER_KEY => $manager];
    }

    /**
     * Return array of available config managers without duplicates and exclude global manager
     *
     * @return array
     */
    public function getAvailableConfigManagers(): array
    {
        $configManagers = [];

        foreach ($this->configManagers as $configManager) {
            if ($configManager[self::SCOPE_KEY] !== 'global') {
                $configManagers[$configManager[self::SCOPE_KEY]] = $configManager[self::MANAGER_KEY];
            }
        }

        return $configManagers;
    }
}
