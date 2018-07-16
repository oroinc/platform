<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\EventListener\ORM\LocalizationListener;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Translation\Strategy\LocalizationFallbackStrategy;

class LocalizationListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LocalizationListener
     */
    private $listener;

    /**
     * @var LocalizationFallbackStrategy|\PHPUnit\Framework\MockObject\MockObject
     */
    private $strategy;

    /**
     * @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $localizationManager;

    protected function setUp()
    {
        $this->strategy = $this
            ->getMockBuilder(LocalizationFallbackStrategy::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->strategy->expects($this->once())->method('clearCache');

        $this->localizationManager = $this
            ->getMockBuilder(LocalizationManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->localizationManager->expects($this->once())->method('clearCache');

        $this->listener = new LocalizationListener($this->strategy, $this->localizationManager);
    }

    public function testPostPersist()
    {
        $args = $this->getLifecycleEventArgsMock();
        $this->listener->postPersist(new Localization(), $args);
    }

    public function testPostUpdate()
    {
        $args = $this->getLifecycleEventArgsMock();
        $this->listener->postUpdate(new Localization(), $args);
    }

    public function testPostRemove()
    {
        $args = $this->getLifecycleEventArgsMock();
        $this->listener->postRemove(new Localization(), $args);
    }

    /**
     * @return LifecycleEventArgs|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getLifecycleEventArgsMock()
    {
        return $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
