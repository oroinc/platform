<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class EmailActivityManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActivityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $activityManager;

    /** @var EmailActivityListProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $emailActivityListProvider;

    /** @var EmailThreadProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $emailThreadProvider;

    /** @var TokenStorage|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var ServiceLink|\PHPUnit\Framework\MockObject\MockObject */
    private $serviceLink;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var array|null */
    private $owners;

    /** @var EmailActivityManager */
    private $emailActivityManager;

    protected function setUp(): void
    {
        $this->activityManager = $this->createMock(ActivityManager::class);
        $this->emailActivityListProvider = $this->createMock(EmailActivityListProvider::class);
        $this->emailThreadProvider = $this->createMock(EmailThreadProvider::class);
        $this->tokenStorage = $this->createMock(TokenStorage::class);
        $this->serviceLink = $this->createMock(ServiceLink::class);
        $this->em = $this->createMock(EntityManager::class);

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
            ->willReturn(true);

        $this->assertTrue(
            $this->emailActivityManager->addAssociation($email, $target)
        );
    }

    /**
     * @dataProvider dataHandlePostFlushProvider
     */
    public function testHandlePostFlushWithoutQueue(Email $email, Email $email2, array $params, array $methods)
    {
        $emails = [];
        if (isset($params['createQueue']) && $params['createQueue'] === 1) {
            $emails[] = $email;
        }

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->addMethods(['findByThread'])
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->exactly($methods['repository.findByThread']['amountCall']))
            ->method('findByThread')
            ->withAnyParameters()
            ->willReturn([$email2]);

        $this->em->expects($this->exactly($methods['entityManager.getRepository']['amountCall']))
            ->method('getRepository')
            ->with(Email::class)
            ->willReturn($repository);

        $this->emailActivityListProvider->expects($this->exactly($methods['getTargetEntities']['amountCall']))
            ->method('getTargetEntities')
            ->willReturn($methods['getTargetEntities']['return']);

        $this->emailThreadProvider->expects($this->exactly($methods['getEmailReferences']['amountCall']))
            ->method('getEmailReferences')
            ->willReturn([$email2]);

        $this->emailActivityManager->updateActivities($emails);
    }

    public function testHandlePostFlushEmptyThread()
    {
        $this->emailActivityListProvider->expects($this->any())
            ->method('getTargetEntities')
            ->willReturn([]);

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
        $this->assertEquals([$anotherUser], $result1);

        $contexts2 = ['one', 'two', 'three'];
        $otherContexts2 = ['two', 'three'];
        $result2 = $this->emailActivityManager->getContextsDiff($contexts2, $otherContexts2);
        $this->assertEquals(['one'], $result2);
    }

    private function getEmailEntity(int $id = null, int $threadId = null): Email
    {
        if (!$this->owners) {
            $this->owners = [
                new TestUser('1'),
                new TestUser('2'),
                new TestUser('3'),
                new TestUser('4')
            ];
        }

        $email = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addActivityTarget', 'getId'])
            ->getMock();

        $email->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        if (null !== $threadId) {
            $thread = new EmailThread();
            ReflectionUtil::setId($thread, $threadId);
            $email->setThread($thread);
        }

        $senderEmailAddress = new EmailAddress();
        $senderEmailAddress->setOwner($this->owners[0]);
        $email->setFromEmailAddress($senderEmailAddress);

        $this->addEmailRecipient($email, $this->owners[1]);
        $this->addEmailRecipient($email, $this->owners[2]);
        $this->addEmailRecipient($email, $this->owners[3]);
        $this->addEmailRecipient($email, $this->owners[0]);
        $this->addEmailRecipient($email, $this->owners[1]);
        $this->addEmailRecipient($email, null);

        return $email;
    }

    private function addEmailRecipient(Email $email, ?EmailOwnerInterface $owner): void
    {
        $emailAddress = new EmailAddress();
        $emailAddress->setOwner($owner);

        $recipient = new EmailRecipient();
        $recipient->setEmailAddress($emailAddress);

        $email->addRecipient($recipient);
    }

    public function dataHandlePostFlushProvider(): array
    {
        return [
            'empty Queue' => $this->getProviderConfigEmptyQueue(),
            'with Queue without Thread' =>$this->getProviderConfigEmptyThread(),
            'with Queue with Thread with getTargetEntities'=> $this->getProviderConfigWithTargetEntities(),
            'with Queue with Thread without getTargetEntities'=> $this->getProviderConfigWithoutTargetEntities()
        ];
    }

    private function getProviderConfigEmptyQueue(): array
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

    private function getProviderConfigWithTargetEntities(): array
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

    private function getProviderConfigEmptyThread(): array
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

    private function getProviderConfigWithoutTargetEntities(): array
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
