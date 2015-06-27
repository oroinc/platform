<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailManager;
use Oro\Bundle\EmailBundle\Entity\EmailUser;

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
    protected $emailFlagManager;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $securityContext;

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

        $this->emailFlagManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Manager\EmailFlagManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new EmailManager(
            $this->em,
            $this->emailThreadManager,
            $this->emailThreadProvider,
            $this->emailFlagManager,
            $this->securityContext
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
}
