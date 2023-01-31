<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\EventListener\ORM;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\EventListener\ORM\LocalizationListener;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Translation\Strategy\LocalizationFallbackStrategy;

class LocalizationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocalizationFallbackStrategy|\PHPUnit\Framework\MockObject\MockObject */
    private $strategy;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var LocalizationListener */
    private $listener;

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

    public function testPostPersist()
    {
        $args = $this->createMock(LifecycleEventArgs::class);
        $this->listener->postPersist(new Localization(), $args);
    }

    public function testPostUpdate()
    {
        $args = $this->createMock(LifecycleEventArgs::class);
        $this->listener->postUpdate(new Localization(), $args);
    }

    public function testPostRemove()
    {
        $args = $this->createMock(LifecycleEventArgs::class);
        $this->listener->postRemove(new Localization(), $args);
    }
}
