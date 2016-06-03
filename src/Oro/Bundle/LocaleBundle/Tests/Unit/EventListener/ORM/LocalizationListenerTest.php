<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\EventListener\ORM\LocalizationListener;
use Oro\Bundle\LocaleBundle\Translation\Strategy\LocalizationFallbackStrategy;

class LocalizationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LocalizationListener
     */
    protected $listener;

    /**
     * @var LocalizationFallbackStrategy|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $strategy;

    protected function setUp()
    {
        $this->strategy = $this->getMockBuilder(
            'Oro\Bundle\LocaleBundle\Translation\Strategy\LocalizationFallbackStrategy'
        )->disableOriginalConstructor()->getMock();
        $this->listener = new LocalizationListener($this->strategy);
    }

    public function testPostPersist()
    {
        $args = $this->getLifecycleEventArgsMock();
        $this->strategy->expects($this->once())
            ->method('clearCache');
        $this->listener->postPersist(new Localization(), $args);
    }

    public function testPostUpdate()
    {
        $args = $this->getLifecycleEventArgsMock();
        $this->strategy->expects($this->once())
            ->method('clearCache');
        $this->listener->postUpdate(new Localization(), $args);
    }

    /**
     * @return LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLifecycleEventArgsMock()
    {
        return $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()->getMock();
    }
}
