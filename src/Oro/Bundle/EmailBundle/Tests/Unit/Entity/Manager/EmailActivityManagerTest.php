<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

/**
 * Class EmailActivityManagerTest
 * @package Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class EmailActivityManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $activityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailActivityListProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailThreadProvider;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /** @var TokenStorage|\PHPUnit_Framework_MockObject_MockObject */
    protected $tokenStorage;

    /** @var ServiceLink|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityOwnerAccessorLink;

    /** @var EmailActivityManager */
    private $manager;

    private $owners;

    protected function setUp()
    {
        $this->activityManager = $this->getMockBuilder('Oro\Bundle\ActivityBundle\Manager\ActivityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailActivityListProvider = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider')
            ->disableOriginalConstructor()
            ->setMethods(['getTargetEntities'])
            ->getMock();
        $this->emailThreadProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenStorage = $this
            ->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityOwnerAccessorLink = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new EmailActivityManager(
            $this->activityManager,
            $this->emailActivityListProvider,
            $this->emailThreadProvider,
            $this->tokenStorage,
            $this->entityOwnerAccessorLink
        );

        $this->owners = [
            new TestUser('1'),
            new TestUser('2'),
            new TestUser('3'),
            new TestUser('4')
        ];
    }

    public function testAddAssociation()
    {
        $email  = $this->getEmailEntity();
        $target = new TestUser();

        $this->activityManager->expects($this->once())
            ->method('addActivityTarget')
            ->with($this->identicalTo($email), $this->identicalTo($target))
            ->will($this->returnValue(true));

        $this->assertTrue(
            $this->manager->addAssociation($email, $target)
        );
    }

    public function testHandleOnFlush()
    {
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');

        /**
         * @var $entityManager EntityManager
         */
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->exactly(1))
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));

        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$email, new \stdClass()]));

        $this->manager->handleOnFlush(new OnFlushEventArgs($entityManager));

        $this->assertCount(1, $this->manager->getQueue());
    }

    /**
     * @dataProvider dataHandlePostFlushProvider
     *
     * @param $email
     * @param $email2
     * @param $params
     * @param $methods
     */
    public function testHandlePostFlushWithoutQueue($email, $email2, $params, $methods)
    {
        if (isset($params['createQueue']) && $params['createQueue'] === 1) {
            $this->manager->addEmailToQueue($email);
        }

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->setMethods(['findByThread'])
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->exactly($methods['repository,findByThread']['amountCall']))
            ->method('findByThread')
            ->withAnyParameters()
            ->will($this->returnValue([$email2]));

        $this->entityManager->expects($this->exactly($methods['entityManager.getRepository']['amountCall']))
            ->method('getRepository')
            ->with(Email::ENTITY_CLASS)
            ->will($this->returnValue($repository));

        $this->emailActivityListProvider->expects($this->exactly($methods['getTargetEntities']['amountCall']))
            ->method('getTargetEntities')
            ->will($this->returnValue($methods['getTargetEntities']['return']));


        $this->emailThreadProvider->expects($this->exactly($methods['getEmailReferences']['amountCall']))
            ->method('getEmailReferences')
            ->will($this->returnValue([$email2]));

        $this->entityManager->expects($this->exactly($methods['em.flush']['amountCall']))
         ->method('flush');

        $this->manager->handlePostFlush(new PostFlushEventArgs($this->entityManager));
        $this->assertCount(0, $this->manager->getQueue());
    }

    public function testHandlePostFlushEmptyThread()
    {
        $email = $this->getEmailEntity();
        $this->manager->addEmailToQueue($email);

        $this->emailActivityListProvider
            ->method('getTargetEntities')
            ->will($this->returnValue([]));

        $this->manager->handlePostFlush(new PostFlushEventArgs($this->entityManager));
        $this->assertCount(0, $this->manager->getQueue());
    }

    public function testHandleOnFlushWithoutNewEmails()
    {
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->exactly(1))
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));

        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([new \stdClass()]));

        $this->manager->handleOnFlush(new OnFlushEventArgs($entityManager));

        $this->assertCount(0, $this->manager->getQueue());
    }

    public function testGetContextsDiff()
    {
        $user = new TestUser();
        $user->setId(1);

        $anotherUser = new TestUser();
        $anotherUser->setId(2);

        $thirdUser = new TestUser();
        $thirdUser->setId(3);

        $contexts = [$user, $anotherUser, $thirdUser];
        $otherContexts = [$user, $thirdUser];

        $result = $this->manager->getContextsDiff($contexts, $otherContexts);
        $this->assertEquals($result, [$anotherUser]);

        $contexts = ["one", "two", "three"];
        $otherContexts = ["two", "three"];
        $result = $this->manager->getContextsDiff($contexts, $otherContexts);
        $this->assertEquals($result, ["one"]);
    }

    /**
     * @param integer $id
     * @param integer $thread

     * @return Email|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEmailEntity($id = null, $thread = null)
    {
        /** @var Email $email */
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email', ['addActivityTarget','getId']);

        $email->method('getId')
            ->will($this->returnValue($id));

        $email->setThread($thread);

        $this->addEmailSender($email, $this->owners[0]);

        $this->addEmailRecipient($email, $this->owners[1]);
        $this->addEmailRecipient($email, $this->owners[2]);
        $this->addEmailRecipient($email, $this->owners[3]);
        $this->addEmailRecipient($email, $this->owners[0]);
        $this->addEmailRecipient($email, $this->owners[1]);
        $this->addEmailRecipient($email, null);

        return $email;
    }

    /**
     * @param Email       $email
     * @param object|null $owner
     */
    protected function addEmailSender(Email $email, $owner = null)
    {
        $emailAddr = new EmailAddress();
        $emailAddr->setOwner($owner);

        $email->setFromEmailAddress($emailAddr);
    }

    /**
     * @param Email       $email
     * @param object|null $owner
     */
    protected function addEmailRecipient(Email $email, $owner = null)
    {
        $emailAddr = new EmailAddress();
        $emailAddr->setOwner($owner);

        $recipient = new EmailRecipient();
        $recipient->setEmailAddress($emailAddr);

        $email->addRecipient($recipient);
    }

    public function dataHandlePostFlushProvider()
    {
        return [
            'empty Queue' => $this->getProviderConfigEmptyQueue(),
            'with Queue without Thread' =>$this->getProviderConfigEmptyThread(),
            'with Queue with Thread with getTargetEntities'=> $this->getProviderConfigWithTargetEntities(),
            'with Queue with Thread without getTargetEntities'=> $this->getProviderConfigWithoutTargetEntities()
        ];
    }

    /**
     * @return array
     */
    protected function getProviderConfigEmptyQueue()
    {
        return [
            'email' => $this->getEmailEntity(1),
            'email2' => $this->getEmailEntity(2),
            'params' => [
                'createQueue' => 0
            ],
            'methods' => [
                'em.flush' => [
                    'amountCall' => 0
                ],
                'repository,findByThread' => [
                    'amountCall' => 0
                ],
                'entityManager.getRepository' => [
                    'amountCall' => 0
                ],
                'getEmailReferences' => [
                    'amountCall' => 0
                ],
                'getTargetEntities'=>[
                    'amountCall' => 0,
                    'return' => []
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getProviderConfigWithTargetEntities()
    {
        return [
            'email' => $this->getEmailEntity(1, 1),
            'email2' => $this->getEmailEntity(2, 1),
            'params' => [
                'createQueue' => 1
            ],
            'methods' => [
                'em.flush' => [
                    'amountCall' => 1
                ],
                'repository,findByThread' => [
                    'amountCall' => 2
                ],
                'entityManager.getRepository' => [
                    'amountCall' => 2
                ],
                'getTargetEntities' => [
                    'amountCall' => 3,
                    'return' => [$this->owners[2]]
                ],
                'getEmailReferences' => [
                    'amountCall' => 0
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getProviderConfigEmptyThread()
    {
        return [
            'email' => $this->getEmailEntity(1),
            'email2' => $this->getEmailEntity(2),
            'params' => [
                'createQueue' => 1
            ],
            'methods' => [
                'em.flush' => [
                    'amountCall' => 1
                ],
                'repository,findByThread' => [
                    'amountCall' => 0
                ],
                'entityManager.getRepository' =>[
                    'amountCall' => 0
                ],
                'getTargetEntities'=>[
                    'amountCall' => 1,
                    'return' => []
                ],
                'getEmailReferences' => [
                    'amountCall' => 0
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getProviderConfigWithoutTargetEntities()
    {
        return [
            'email' => $this->getEmailEntity(1, 1),
            'email2' => $this->getEmailEntity(2, 1),
            'params' => [
                'createQueue' => 1
            ],
            'methods' => [
                'em.flush' => [
                    'amountCall' => 1
                ],
                'repository,findByThread' => [
                    'amountCall' => 2
                ],
                'entityManager.getRepository' => [
                    'amountCall' => 2
                ],
                'getTargetEntities' => [
                    'amountCall' => 4,
                    'return' => []
                ],
                'getEmailReferences' => [
                    'amountCall' => 1
                ]
            ]
        ];
    }
}
