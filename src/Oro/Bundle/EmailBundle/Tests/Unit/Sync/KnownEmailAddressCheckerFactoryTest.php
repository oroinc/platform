<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync;

use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerFactory;

class KnownEmailAddressCheckerFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $emailAddressManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager')
            ->disableOriginalConstructor()
            ->getMock();
        $emailAddressHelper = $this->getMockBuilder('Oro\Bundle\EmailBundle\Tools\EmailAddressHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $emailOwnerProviderStorage =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage')
                ->disableOriginalConstructor()
                ->getMock();

        $doctrine->expects($this->exactly(2))
            ->method('getManager')
            ->with(null)
            ->will($this->returnValue($em));
        $em->expects($this->once())
            ->method('isOpen')
            ->will($this->returnValue(false));
        $doctrine->expects($this->once())
            ->method('resetManager');

        $factory = new KnownEmailAddressCheckerFactory(
            $doctrine,
            $emailAddressManager,
            $emailAddressHelper,
            $emailOwnerProviderStorage,
            []
        );

        $result = $factory->create();
        $this->assertInstanceOf('Oro\Bundle\EmailBundle\Sync\KnownEmailAddressChecker', $result);
    }
}
