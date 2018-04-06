<?php

namespace Oro\Bundle\LocaleBundle\EventListener\ORM;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Translation\Strategy\LocalizationFallbackStrategy;

class LocalizationListener
{
    /**
     * @var LocalizationFallbackStrategy
     */
    private $localizationFallbackStrategy;

    /**
     * @var LocalizationManager
     */
    private $localizationManager;

    /**
     * @param LocalizationFallbackStrategy $localizationFallbackStrategy
     * @param LocalizationManager $localizationManager
     */
    public function __construct(
        LocalizationFallbackStrategy $localizationFallbackStrategy,
        LocalizationManager $localizationManager
    ) {
        $this->localizationFallbackStrategy = $localizationFallbackStrategy;
        $this->localizationManager = $localizationManager;
    }

    /**
     * @param Localization       $localization
     * @param LifecycleEventArgs $event
     * @throws \Exception
     */
    public function postUpdate(Localization $localization, LifecycleEventArgs $event)
    {
        $this->handleChanges();
    }

    /**
     * @param Localization       $localization
     * @param LifecycleEventArgs $event
     * @throws \Exception
     */
    public function postPersist(Localization $localization, LifecycleEventArgs $event)
    {
        $this->handleChanges();
    }

    /**
     * @param Localization       $localization
     * @param LifecycleEventArgs $event
     * @throws \Exception
     */
    public function postRemove(Localization $localization, LifecycleEventArgs $event)
    {
        $this->handleChanges();
    }

    private function handleChanges()
    {
        $this->localizationFallbackStrategy->clearCache();
        $this->localizationManager->clearCache();
    }
}
