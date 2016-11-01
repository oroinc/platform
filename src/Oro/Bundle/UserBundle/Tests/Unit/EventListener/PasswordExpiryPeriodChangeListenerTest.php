<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\UserBundle\Provider\PasswordChangePeriodConfigProvider;
use Oro\Bundle\UserBundle\EventListener\PasswordExpiryPeriodChangeListener;

class PasswordExpiryPeriodChangeListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Registry */
    protected $registry;

    /** @var  PasswordChangePeriodConfigProvider */
    protected $provider;

    /** @var \DateTime */
    protected $expiryDate;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->expiryDate = new \DateTime('+3 Days');

        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = $this->getMockBuilder('Oro\Bundle\UserBundle\Provider\PasswordChangePeriodConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Test update value on enabled setting.
     */
    public function testUpdatedValue()
    {
        $this->mockRegistryMethods();

        $this->provider->expects($this->once())
            ->method('isPasswordChangePeriodEnabled')
            ->willReturn(true);
        $this->provider->expects($this->once())
            ->method('getPasswordExpiryDateFromNow')
            ->willReturn($this->expiryDate);

        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->exactly(3))
            ->method('isChanged')
            ->will($this->onConsecutiveCalls(false, false, true));

        $listener = new PasswordExpiryPeriodChangeListener($this->registry, $this->provider);
        $listener->onConfigUpdate($event);
    }

    /**
     * Test Enable/Disable setting.
     */
    public function testEnableSetting()
    {
        $this->mockRegistryMethods();

        $this->provider->expects($this->once())
            ->method('getPasswordExpiryDateFromNow')
            ->willReturn($this->expiryDate);

        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->exactly(2))
            ->method('isChanged')
            ->will($this->onConsecutiveCalls(true, true));

        $listener = new PasswordExpiryPeriodChangeListener($this->registry, $this->provider);
        $listener->onConfigUpdate($event);
    }

    /**
     * Test changing value on disabled setting.
     */
    public function testChangeValueWhenSettingDisabled()
    {
        $this->provider->expects($this->once())
            ->method('isPasswordChangePeriodEnabled')
            ->willReturn(false);

        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('isChanged')
            ->willReturn(false);

        $listener = new PasswordExpiryPeriodChangeListener($this->registry, $this->provider);
        $listener->onConfigUpdate($event);
    }

    /**
     * Add DB query stubs/
     */
    private function mockRegistryMethods()
    {
        $repo = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('setPasswordExpiresAt')
            ->with($this->expiryDate);

        $this->registry->expects($this->atLeastOnce())
            ->method('getRepository')
            ->willReturn($repo);
    }
}
