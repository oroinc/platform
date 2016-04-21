<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailManager;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class EmailManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $emailThreadManager;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $emailThreadProvider;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $queryBuilder;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $mailboxManager;

    protected function setUp()
    {
        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['getQuery', 'getResult'])
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->setMethods(['getRepository', 'getEmailUserByThreadId', 'flush', 'persist'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailThreadManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailThreadProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mailboxManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new EmailManager(
            $this->em,
            $this->emailThreadManager,
            $this->emailThreadProvider,
            $this->securityFacade,
            $this->mailboxManager
        );
    }

    /**
     * @dataProvider dataProvider
     *
     * @param bool $isSeen
     * @param bool $newSeen
     * @param bool $seen
     * @param bool $flush
     * @param int $calls
     * @param int $flushCalls
     */
    public function testSetEmailSeenChanges($isSeen, $newSeen, $seen, $flush, $calls, $flushCalls)
    {
        $emailUser = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailUser');
        $emailUser->expects($this->once())
            ->method('isSeen')
            ->will($this->returnValue($isSeen));
        $emailUser->expects($this->exactly($calls))
            ->method('setSeen')
            ->with($newSeen);
        $this->em->expects($this->exactly($flushCalls))
            ->method('flush');

        $this->manager->setEmailUserSeen($emailUser, $seen, $flush);
    }

    public function dataProvider()
    {
        return [
            'unseen when seen with flush' => [
                'isSeen' => true,
                'newSeen' => false,
                'seen' => false,
                'flush' => true,
                'calls' => 1,
                'flushCalls' => 1
            ],
            'unseen when unseen with flush' => [
                'isSeen' => false,
                'newSeen' => false,
                'seen' => false,
                'flush' => true,
                'calls' => 0,
                'flushCalls' => 0
            ],
            'seen when unseen with flush' => [
                'isSeen' => false,
                'newSeen' => true,
                'seen' => true,
                'flush' => true,
                'calls' => 1,
                'flushCalls' => 1
            ],
            'seen when seen with flush' => [
                'isSeen' => true,
                'newSeen' => true,
                'seen' => true,
                'flush' => true,
                'calls' => 0,
                'flushCalls' => 0
            ],
            'seen when unseen without flush' => [
                'isSeen' => false,
                'newSeen' => true,
                'seen' => true,
                'flush' => false,
                'calls' => 1,
                'flushCalls' => 0
            ]
        ];
    }

    public function testSetEmailSeenChangesDefs()
    {
        $emailUser = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailUser');
        $emailUser->expects($this->once())
            ->method('isSeen')
            ->will($this->returnValue(false));
        $emailUser->expects($this->once())
            ->method('setSeen')
            ->with(true);
        $this->em->expects($this->never())
            ->method('flush');

        $this->manager->setEmailUserSeen($emailUser);
    }

    public function testToggleEmailUserSeen()
    {
        $threadArray = [new EmailUser()];

        $emailUser = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailUser')
            ->setMethods(['getEmail', 'getThread', 'getId', 'setSeen', 'isSeen', 'getOwner'])
            ->disableOriginalConstructor()
            ->getMock();

        $emailUser->expects($this->exactly(2))
            ->method('getEmail')
            ->will($this->returnSelf());

        $emailUser->expects($this->exactly(2))
            ->method('getThread')
            ->will($this->returnSelf());

        $emailUser->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $emailUser->expects($this->once())
            ->method('setSeen')
            ->with(false);
        $emailUser->expects($this->exactly(2))
            ->method('isSeen')
            ->will($this->returnValue(true));
        $emailUser->expects($this->exactly(2))
            ->method('getOwner')
            ->will($this->returnValue(true));
        $this->em->expects($this->once())
            ->method('flush');
        $this->em->expects($this->exactly(2))
            ->method('persist');

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($this->queryBuilder));

        $this->queryBuilder->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($threadArray));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->em));

        $this->em->expects($this->once())
            ->method('getEmailUserByThreadId')
            ->will($this->returnValue($this->queryBuilder));

        $this->manager->toggleEmailUserSeen($emailUser);
    }

    public function testMarkAllEmailsAsSeenEmpty()
    {
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository')
            ->setMethods(
                [
                    'createQueryBuilder',
                    'andWhere',
                    'setParameter',
                    'getQuery',
                    'execute',
                    'expr',
                    'eq',
                    'getResult',
                    'andX'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())->method('createQueryBuilder')->will($this->returnValue($repository));
        $repository->expects($this->exactly(2))->method('andWhere')->will($this->returnValue($repository));
        $repository->expects($this->exactly(3))->method('setParameter')->will($this->returnValue($repository));
        $repository->expects($this->once())->method('getQuery')->will($this->returnValue($repository));
        $repository->expects($this->exactly(4))->method('expr')->will($this->returnValue($repository));
        $repository->expects($this->exactly(3))->method('eq')->will($this->returnValue($repository));
        $repository->expects($this->exactly(1))->method('andX')->will($this->returnValue($repository));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $repository->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue([]));

        $this->manager->markAllEmailsAsSeen($user, $organization);
    }

    /**
     * @dataProvider dataSeenProvider
     *
     * @param bool $isSeen
     * @param int $setSeenCalls
     */
    public function testMarkAllEmailsAsSeen($isSeen, $setSeenCalls)
    {
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()
            ->getMock();

        $emailUser = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailUser');
        $emailUser->expects($this->once())
            ->method('isSeen')
            ->will($this->returnValue($isSeen));
        $emailUser->expects($this->exactly($setSeenCalls))
            ->method('setSeen')
            ->with(true);
        $this->em->expects($this->once())
            ->method('flush');

        $repository = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository')
            ->setMethods(
                ['createQueryBuilder', 'getQuery', 'getResult', 'findUnseenUserEmail']
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->em->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $repository->expects($this->once())
            ->method('findUnseenUserEmail')
            ->will($this->returnValue($repository));

        $repository->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($repository));

        $repository->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue([$emailUser]));

        $this->manager->markAllEmailsAsSeen($user, $organization);
    }

    public function testSetSeenStatus()
    {
        $user       = new User();
        $organization = new Organization();
        $email = new Email();
        $emailUsers = [
            new EmailUser(),
            new EmailUser(),
            new EmailUser()
        ];

        array_map(
            function (EmailUser $emailUser) use ($email) {
                $emailUser->setEmail($email);
                $this->assertFalse($emailUser->isSeen());
            },
            $emailUsers
        );

        $this->securityFacade
            ->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);
        $this->securityFacade
            ->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);
        $emailUsersRepo = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $emailUsersRepo
            ->expects($this->once())
            ->method('getAllEmailUsersByEmail')
            ->with($email, $user, $organization, false)
            ->willReturn($emailUsers);
        $this->em
            ->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:EmailUser')
            ->willReturn($emailUsersRepo);

        $this->manager->setSeenStatus($email, true);

        array_map(
            function (EmailUser $emailUser) use ($email) {
                $this->assertTrue($emailUser->isSeen());
            },
            $emailUsers
        );
    }

    public function dataSeenProvider()
    {
        return [
            'seen' => [
                'isSeen' => true,
                'setSeenCalls' => 0
            ],
            'unseen to seen' => [
                'isSeen' => false,
                'setSeenCalls' => 1,
            ]
        ];
    }
}
