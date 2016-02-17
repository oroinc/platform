<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager\DTO;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ImapBundle\Manager\ImapClearManager;

class ImapClearManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ImapClearManager */
    protected $manager;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    protected function setUp()
    {
        $this->logger = $this->getMock('Psr\Log\LoggerInterface');
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->setMethods(['select', 'getRepository', 'flush'])
            ->disableOriginalConstructor()
            ->getMock();

        $listener = $this->getMock('Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface');

        $this->manager = new ImapClearManager($this->em, $listener);
        $this->manager->setLogger($this->logger);
    }

    public function testClearNothing()
    {
        $repoUserEmailOrigin = $this
            ->getMockBuilder('Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repoUserEmailOrigin->expects($this->once())
            ->method('findAll')->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')->willReturn($repoUserEmailOrigin);

        $this->assertFalse($this->manager->clear(null));
    }

    public function testClearAllOriginsNotClearActive()
    {
        $repoUserEmailOrigin = $this
            ->getMockBuilder('Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $emailOrigin = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Entity\UserEmailOrigin')
            ->setMethods(['getId', '__toString', 'getFolders', 'isActive'])
            ->disableOriginalConstructor()
            ->getMock();
        $imapEmailFolder = $this
            ->getMockBuilder('Oro\Bundle\ImapBundle\Entity\ImapEmailFolder')
            ->setMethods(['getId', 'getFolder', 'isSyncEnabled'])
            ->disableOriginalConstructor()
            ->getMock();
        $emailFolder = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailFolder')
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $repoUserEmailOrigin->expects($this->once())
            ->method('findAll')->willReturn([$emailOrigin]);
        $repoUserEmailOrigin->expects($this->once())
            ->method('findOneBy')->willReturn($imapEmailFolder);

        $emailOrigin->expects($this->once())
            ->method('getId')->will($this->returnValue(1));
        $emailOrigin->expects($this->exactly(3))
            ->method('isActive')->will($this->returnValue(true));
        $emailOrigin->expects($this->once())
            ->method('getFolders')->will($this->returnValue([$imapEmailFolder]));
        $emailOrigin->expects($this->once())
            ->method('__toString')->will($this->returnValue(''));

        $imapEmailFolder->expects($this->never())
            ->method('getFolder')->will($this->returnValue($emailFolder));
        $imapEmailFolder->expects($this->once())
            ->method('isSyncEnabled')->willReturn(true);

        $this->em->expects($this->exactly(2))
            ->method('getRepository')->willReturn($repoUserEmailOrigin);

        $this->assertTrue($this->manager->clear(null));
    }

    public function testClearSomeOriginsNotClearActive()
    {
        $repoUserEmailOrigin = $this
            ->getMockBuilder('Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $emailOrigin = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Entity\UserEmailOrigin')
            ->setMethods(['getId', '__toString', 'getFolders', 'isActive'])
            ->disableOriginalConstructor()
            ->getMock();
        $imapEmailFolder = $this
            ->getMockBuilder('Oro\Bundle\ImapBundle\Entity\ImapEmailFolder')
            ->setMethods(['getId', 'getFolder', 'isSyncEnabled'])
            ->disableOriginalConstructor()
            ->getMock();
        $emailFolder = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailFolder')
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $repoUserEmailOrigin->expects($this->once())
            ->method('find')->willReturn($emailOrigin);
        $repoUserEmailOrigin->expects($this->once())
            ->method('findOneBy')->willReturn($imapEmailFolder);

        $emailOrigin->expects($this->once())
            ->method('getId')->will($this->returnValue(1));
        $emailOrigin->expects($this->exactly(3))
            ->method('isActive')->will($this->returnValue(true));
        $emailOrigin->expects($this->once())
            ->method('getFolders')->will($this->returnValue([$imapEmailFolder]));
        $emailOrigin->expects($this->once())
            ->method('__toString')->will($this->returnValue(''));

        $imapEmailFolder->expects($this->never())
            ->method('getFolder')->will($this->returnValue($emailFolder));
        $imapEmailFolder->expects($this->once())
            ->method('isSyncEnabled')->willReturn(true);

        $this->em->expects($this->exactly(2))
            ->method('getRepository')->willReturn($repoUserEmailOrigin);

        $this->assertTrue($this->manager->clear(1));
    }
}
