<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class EmailActivityManagerTest extends \PHPUnit\Framework\TestCase
{
    protected $owners;

    protected $activityManager;
    protected $emailActivityListProvider;
    protected $emailThreadProvider;
    protected $tokenStorage;
    protected $serviceLink;
    protected $em;

    protected $emailActivityManager;

    protected function setUp()
    {
        $this->owners = [
            new TestUser('1'),
            new TestUser('2'),
            new TestUser('3'),
            new TestUser('4')
        ];

        $this->activityManager = $this->getMockBuilder('Oro\Bundle\ActivityBundle\Manager\ActivityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailActivityListProvider = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailThreadProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenStorage = $this
            ->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->serviceLink = $this->createMock(ServiceLink::class);

        $this->entityOwnerAccessorLink = $this->createMock(ServiceLink::class);

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailActivityManager = new EmailActivityManager(
            $this->activityManager,
            $this->emailActivityListProvider,
            $this->emailThreadProvider,
            $this->tokenStorage,
            $this->serviceLink,
            $this->em
        );
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
            $this->emailActivityManager->addAssociation($email, $target)
        );
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
        $emails = [];
        if (isset($params['createQueue']) && $params['createQueue'] === 1) {
            $emails[] = $email;
        }

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->setMethods(['findByThread'])
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->exactly($methods['repository.findByThread']['amountCall']))
            ->method('findByThread')
            ->withAnyParameters()
            ->will($this->returnValue([$email2]));

        $this->em->expects($this->exactly($methods['entityManager.getRepository']['amountCall']))
            ->method('getRepository')
            ->with(Email::ENTITY_CLASS)
            ->will($this->returnValue($repository));

        $this->emailActivityListProvider->expects($this->exactly($methods['getTargetEntities']['amountCall']))
            ->method('getTargetEntities')
            ->will($this->returnValue($methods['getTargetEntities']['return']));


        $this->emailThreadProvider->expects($this->exactly($methods['getEmailReferences']['amountCall']))
            ->method('getEmailReferences')
            ->will($this->returnValue([$email2]));

        $this->emailActivityManager->updateActivities($emails);
    }

    public function testHandlePostFlushEmptyThread()
    {
        $this->emailActivityListProvider
            ->method('getTargetEntities')
            ->will($this->returnValue([]));

        $this->emailActivityManager->updateActivities([$this->getEmailEntity()]);
    }

    public function testGetContextsDiff()
    {
        $user = new TestUser();
        $user->setId(1);

        $anotherUser = new TestUser();
        $anotherUser->setId(2);

        $thirdUser = new TestUser();
        $thirdUser->setId(3);

        $contexts1 = [$user, $anotherUser, $thirdUser];
        $otherContexts1 = [$user, $thirdUser];
        $result1 = $this->emailActivityManager->getContextsDiff($contexts1, $otherContexts1);
        $this->assertEquals($result1, [$anotherUser]);

        $contexts2 = ["one", "two", "three"];
        $otherContexts2 = ["two", "three"];
        $result2 = $this->emailActivityManager->getContextsDiff($contexts2, $otherContexts2);
        $this->assertEquals($result2, ["one"]);
    }

    /**
     * @param integer $id
     * @param integer $thread

     * @return Email|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getEmailEntity($id = null, $thread = null)
    {
        /** @var Email $email */
        $email = $this->createPartialMock('Oro\Bundle\EmailBundle\Entity\Email', ['addActivityTarget','getId']);

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
                'repository.findByThread' => [
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
                'repository.findByThread' => [
                    'amountCall' => 1
                ],
                'entityManager.getRepository' => [
                    'amountCall' => 1
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
                'repository.findByThread' => [
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
                'repository.findByThread' => [
                    'amountCall' => 0
                ],
                'entityManager.getRepository' => [
                    'amountCall' => 0
                ],
                'getTargetEntities' => [
                    'amountCall' => 3,
                    'return' => []
                ],
                'getEmailReferences' => [
                    'amountCall' => 1
                ]
            ]
        ];
    }
}
