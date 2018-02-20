<?php

namespace Oro\Bundle\TranslationBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigValueRepository;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;

class LanguagesChangeListener
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /**
     * @param ConfigManager $configScopeManager
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ConfigManager $configScopeManager, ManagerRegistry $managerRegistry)
    {
        $this->configManager = $configScopeManager;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function onConfigUpdate(ConfigUpdateEvent $event)
    {
        if (!$event->isChanged('oro_locale.languages') || 'global' !== $event->getScope()) {
            return;
        }
        $availableLanguages = $event->getNewValue('oro_locale.languages');
        /** @var ConfigValueRepository $repository */
        $repository = $this->managerRegistry->getManagerForClass(ConfigValue::class)->getRepository(ConfigValue::class);
        $values = $repository->getConfigValues(
            $this->configManager->getScopeEntityName(),
            'oro_locale',
            'language'
        );
        foreach ($values as $value) {
            if (!in_array($value->getValue(), $availableLanguages, true)) {
                $this->configManager->reset('oro_locale.language', $value->getConfig()->getRecordId());
                $this->configManager->flush($value->getConfig()->getRecordId());
            }
        }
    }
}
