<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Datagrid;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use Oro\Bundle\EmailBundle\Datagrid\MailboxNameHelper;
use Oro\Bundle\EmailBundle\Datagrid\OriginFolderFilterProvider;
use Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailFolder;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestMailbox;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUserEmailOrigin;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class OriginFolderFilterProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    protected $doctrine;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MailboxNameHelper */
    protected $mailboxNameHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MailboxRepository */
    protected $mailboxRepository;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $originRepository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AbstractQuery */
    protected $originQuery;

    /** @var OriginFolderFilterProvider */
    protected $originFolderFilterProvider;

    public function setUp()
    {
        $this->mailboxRepository = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->originQuery = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();

        $originQb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $originQb->expects($this->any())
            ->method('select')
            ->will($this->returnSelf());
        $originQb->expects($this->any())
            ->method('leftJoin')
            ->will($this->returnSelf());
        $originQb->expects($this->any())
            ->method('andWhere')
            ->will($this->returnSelf());
        $originQb->expects($this->any())
            ->method('setParameters')
            ->will($this->returnSelf());
        $originQb->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($this->originQuery));

        $this->originRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder', 'findAvailableMailboxes'])
            ->getMock();
        $this->originRepository->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($originQb));

        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->mailboxNameHelper = $this->createMock(MailboxNameHelper::class);

        $this->originFolderFilterProvider = new OriginFolderFilterProvider(
            $this->doctrine,
            $this->tokenAccessor,
            $this->mailboxNameHelper
        );
    }

    public function testEmptyOrigins()
    {
        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($this->originRepository));

        $this->originQuery->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->originRepository->expects($this->once())
            ->method('findAvailableMailboxes')
            ->willReturn([]);

        $result = $this->originFolderFilterProvider->getListTypeChoices();

        $this->assertEmpty($result);
    }

    public function testPersonalOrigin()
    {
        $origin1 = new TestEmailOrigin(1);
        $origin1->setMailboxName('testName1');
        $folder1 = new TestEmailFolder(1);
        $folder2 = new TestEmailFolder(2);
        $folder3 = new TestEmailFolder(3);
        $folder1->setFullName('Folder1');
        $folder1->setSyncEnabled(true);
        $folder2->setFullName('Folder2');
        $folder2->setSyncEnabled(true);
        $folder3->setFullName('Folder - disabled');
        $origin1->addFolder($folder1);
        $origin1->addFolder($folder2);
        $origin1->addFolder($folder3);

        $origin2 = new TestEmailOrigin(2);
        $origin2->setMailboxName('testName2');
        $origin2->setActive(false);
        $folder3 = new TestEmailFolder(3);
        $folder3->setSyncEnabled(true);
        $folder3->setFullName('Folder3');
        $origin2->addFolder($folder3);

        $origin1MailboxName = 'Origin 1 Mailbox';
        $origin2MailboxName = 'Origin 2 Mailbox';

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($this->originRepository));

        $this->originQuery->expects($this->once())
            ->method('getResult')
            ->willReturn([$origin1, $origin2]);

        $this->originRepository->expects($this->once())
            ->method('findAvailableMailboxes')
            ->willReturn([]);

        $this->mailboxNameHelper->expects(self::any())
            ->method('getMailboxName')
            ->willReturnMap([
                [get_class($origin1), $origin1->getMailboxName(), null, $origin1MailboxName],
                [get_class($origin2), $origin2->getMailboxName(), null, $origin2MailboxName],
            ]);

        $result = $this->originFolderFilterProvider->getListTypeChoices();

        $this->assertNotEmpty($result);
        $this->assertCount(2, $result);
        $this->assertEquals($origin1->isActive(), $result[$origin1MailboxName]['active']);
        $this->assertEquals($origin2->isActive(), $result[$origin2MailboxName]['active']);
        $this->assertCount(2, $result[$origin1MailboxName]['folder']);
        $this->assertCount(1, $result[$origin2MailboxName]['folder']);
        $this->assertEquals($folder2->getId(), $result[$origin1MailboxName]['folder'][$folder2->getFullName()]);
    }

    public function testMailboxOrigins()
    {
        $user1 = new TestUser();
        $mailbox1 = new TestMailbox();
        $origin1 = new TestUserEmailOrigin(1);
        $origin1->setMailboxName('testName1');
        $origin1->setUser($user1);
        $folder1 = new TestEmailFolder(1);
        $folder2 = new TestEmailFolder(2);
        $folder3 = new TestEmailFolder(3);
        $folder1->setFullName('Folder1');
        $folder1->setSyncEnabled(true);
        $folder2->setFullName('Folder2');
        $folder2->setSyncEnabled(true);
        $folder3->setFullName('Folder - disabled');
        $origin1->addFolder($folder1);
        $origin1->addFolder($folder2);
        $origin1->addFolder($folder3);
        $mailbox1->setOrigin($origin1);

        $mailbox2 = new TestMailbox();
        $mailbox2->setLabel('Box2');
        $origin2 = new TestUserEmailOrigin(2);
        $origin2->setMailboxName('testName2');
        $origin2->setUser($user1);
        $origin2->setActive(false);
        $folder3 = new TestEmailFolder(3);
        $folder3->setSyncEnabled(true);
        $folder3->setFullName('Folder3');
        $origin2->addFolder($folder3);
        $mailbox2->setOrigin($origin2);

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($this->originRepository));

        $this->originQuery->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->originRepository->expects($this->once())
            ->method('findAvailableMailboxes')
            ->willReturn([$mailbox1, $mailbox2]);

        $result = $this->originFolderFilterProvider->getListTypeChoices();

        $this->assertNotEmpty($result);
        $this->assertCount(2, $result);
        $this->assertEquals($origin1->isActive(), $result[$mailbox1->getLabel()]['active']);
        $this->assertEquals($origin2->isActive(), $result[$mailbox2->getLabel()]['active']);
        $this->assertCount(2, $result[$mailbox1->getLabel()]['folder']);
        $this->assertCount(1, $result[$mailbox2->getLabel()]['folder']);
        $this->assertEquals($folder2->getId(), $result[$mailbox1->getLabel()]['folder'][$folder2->getFullName()]);
    }
}
