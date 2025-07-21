<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\EventListener\ORM;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\EventListener\ORM\LocalizationListener;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Translation\Strategy\LocalizationFallbackStrategy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocalizationListenerTest extends TestCase
{
    private LocalizationFallbackStrategy&MockObject $strategy;
    private LocalizationManager&MockObject $localizationManager;
    private LocalizationListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->strategy = $this->createMock(LocalizationFallbackStrategy::class);
        $this->strategy->expects($this->once())
            ->method('clearCache');

        $this->localizationManager = $this->createMock(LocalizationManager::class);
        $this->localizationManager->expects($this->once())
            ->method('clearCache');

        $this->listener = new LocalizationListener($this->strategy, $this->localizationManager);
    }

    public function testPostPersist(): void
    {
        $args = $this->createMock(LifecycleEventArgs::class);
        $this->listener->postPersist(new Localization(), $args);
    }

    public function testPostUpdate(): void
    {
        $args = $this->createMock(LifecycleEventArgs::class);
        $this->listener->postUpdate(new Localization(), $args);
    }

    public function testPostRemove(): void
    {
        $args = $this->createMock(LifecycleEventArgs::class);
        $this->listener->postRemove(new Localization(), $args);
    }
}
