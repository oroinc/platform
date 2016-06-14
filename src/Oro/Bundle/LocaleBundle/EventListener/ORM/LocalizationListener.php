<?php

namespace Oro\Bundle\LocaleBundle\EventListener\ORM;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Translation\Strategy\LocalizationFallbackStrategy;

class LocalizationListener
{
    /**
     * @var LocalizationFallbackStrategy
     */
    protected $localizationFallbackStrategy;

    /**
     * @param LocalizationFallbackStrategy $localizationFallbackStrategy
     */
    public function __construct(LocalizationFallbackStrategy $localizationFallbackStrategy)
    {
        $this->localizationFallbackStrategy = $localizationFallbackStrategy;
    }

    /**
     * @param Localization $localization
     * @param LifecycleEventArgs $event
     * @throws \Exception
     */
    public function postUpdate(Localization $localization, LifecycleEventArgs $event)
    {
        $this->handleChanges();
    }

    /**
     * @param Localization $localization
     * @param LifecycleEventArgs $event
     * @throws \Exception
     */
    public function postPersist(Localization $localization, LifecycleEventArgs $event)
    {
        $this->handleChanges();
    }

    protected function handleChanges()
    {
        $this->localizationFallbackStrategy->clearCache();
    }
}
