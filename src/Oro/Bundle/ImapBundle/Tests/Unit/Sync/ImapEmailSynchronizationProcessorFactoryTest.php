<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Sync;

use Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizationProcessorFactory;
use Psr\Log\LoggerInterface;

class ImapEmailSynchronizationProcessorFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $emailEntityBuilder = $this->getMockBuilder('Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $emailManager = $this->getMockBuilder('Oro\Bundle\ImapBundle\Manager\ImapEmailManager')
            ->disableOriginalConstructor()
            ->getMock();
        $removeManager = $this->getMockBuilder('Oro\Bundle\ImapBundle\Sync\ImapEmailRemoveManager')
            ->disableOriginalConstructor()
            ->getMock();
        $knownEmailAddressChecker = $this->createMock('Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerInterface');
        $logger = $this->createMock(LoggerInterface::class);


        $doctrine->expects($this->exactly(2))
            ->method('getManager')
            ->with(null)
            ->will($this->returnValue($em));
        $em->expects($this->once())
            ->method('isOpen')
            ->will($this->returnValue(false));
        $doctrine->expects($this->once())
            ->method('resetManager');

        $factory = new ImapEmailSynchronizationProcessorFactory(
            $doctrine,
            $emailEntityBuilder,
            $removeManager
        );

        $factory->setLogger($logger);

        $result = $factory->create($emailManager, $knownEmailAddressChecker);
        $this->assertInstanceOf('Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizationProcessor', $result);
    }
}
