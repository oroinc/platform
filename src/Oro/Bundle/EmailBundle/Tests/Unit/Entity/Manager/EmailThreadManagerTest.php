<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailEntity;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestThread;
use Oro\Component\Testing\ReflectionUtil;

class EmailThreadManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EmailThreadProvider */
    private $emailThreadProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManager */
    private $em;

    /** @var EmailThreadManager */
    private $emailThreadManager;

    /** @var array */
    private $fixtures = [];

    protected function setUp(): void
    {
        $thread1 = new TestThread(1);
        $thread2 = new TestThread(2);
        $thread3 = new TestThread(3);
        $this->fixtures = [
            'emails' => [
                1 => new TestEmailEntity(1),
                2 => (new TestEmailEntity(2))
                        ->setThread($thread1)
                        ->setHead(false),
                3 => (new TestEmailEntity(3))
                        ->setThread($thread2),
                4 => (new TestEmailEntity(4))
                        ->setHead(false)
                        ->setThread($thread3),
                5 => (new TestEmailEntity(5))
                        ->setHead(true)
                        ->setThread($thread3),
            ],
            'threads' => [
                null => new TestThread(),
                1 => $thread1,
                2 => $thread2,
                3 => $thread3,
            ],
        ];

        $this->emailThreadProvider = $this->createMock(EmailThreadProvider::class);
        $this->em = $this->createMock(EntityManager::class);

        $this->emailThreadManager = new EmailThreadManager($this->emailThreadProvider, $this->em);
    }

    private function getEmail(?int $id, ?bool $head, ?EmailThread $thread = null): Email
    {
        $email = new Email();
        if (null !== $id) {
            ReflectionUtil::setId($email, $id);
        }
        $email->setHead($head);
        if (null !== $thread) {
            $email->setThread($thread);
        }

        return $email;
    }

    private function getThread(?int $id): EmailThread
    {
        $thread = new EmailThread();
        if (null !== $id) {
            ReflectionUtil::setId($thread, $id);
        }

        return $thread;
    }

    public function testUpdateThreadsForNewEmailWithoutThread(): void
    {
        $newEmail = $this->getEmail(null, true);

        $this->emailThreadProvider->expects(self::once())
            ->method('getEmailReferences')
            ->with(self::identicalTo($this->em), self::identicalTo($newEmail))
            ->willReturn([]);
        $this->emailThreadProvider->expects(self::once())
            ->method('getReferredEmails')
            ->with(self::identicalTo($this->em), self::identicalTo($newEmail))
            ->willReturn([]);

        $this->emailThreadManager->updateThreads([$newEmail]);

        self::assertNull($newEmail->getThread());
    }

    public function testUpdateThreadsForNewEmailWhenExistsAnotherEmailThatShouldBeAddedToThread(): void
    {
        $newEmail = $this->getEmail(null, true);
        $threadEmail = $this->getEmail(1, true);

        $this->emailThreadProvider->expects(self::once())
            ->method('getEmailReferences')
            ->with(self::identicalTo($this->em), self::identicalTo($newEmail))
            ->willReturn([$threadEmail]);
        $this->emailThreadProvider->expects(self::never())
            ->method('getReferredEmails');

        $this->emailThreadManager->updateThreads([$newEmail]);

        self::assertEquals($this->getThread(null), $newEmail->getThread());
        self::assertSame($newEmail->getThread(), $threadEmail->getThread());
    }

    public function testUpdateThreadsForNewEmailWithinThread(): void
    {
        $newEmail = $this->getEmail(null, true);
        $thread1 = $this->getThread(1);
        $thread2 = $this->getThread(2);
        $threadEmail1 = $this->getEmail(1, true);
        $threadEmail2 = $this->getEmail(2, true, $thread1);
        $threadEmail3 = $this->getEmail(3, true, $thread2);

        $this->emailThreadProvider->expects(self::once())
            ->method('getEmailReferences')
            ->with(self::identicalTo($this->em), self::identicalTo($newEmail))
            ->willReturn([$threadEmail1, $threadEmail2, $threadEmail3]);
        $this->emailThreadProvider->expects(self::never())
            ->method('getReferredEmails');

        $this->emailThreadManager->updateThreads([$newEmail]);

        self::assertEquals($thread1, $newEmail->getThread());
        self::assertEquals($thread1, $threadEmail1->getThread());
        self::assertEquals($thread1, $threadEmail2->getThread());
        self::assertEquals($thread2, $threadEmail3->getThread());
    }

    public function testUpdateThreadsForNewEmailThatIsRootForAlreadyExistingEmails(): void
    {
        $newEmail = $this->getEmail(null, true);
        $thread1 = $this->getThread(1);
        $thread2 = $this->getThread(2);
        $threadEmail1 = $this->getEmail(1, true);
        $threadEmail2 = $this->getEmail(2, true, $thread1);
        $threadEmail3 = $this->getEmail(3, true, $thread2);

        $this->emailThreadProvider->expects(self::once())
            ->method('getEmailReferences')
            ->with(self::identicalTo($this->em), self::identicalTo($newEmail))
            ->willReturn([]);
        $this->emailThreadProvider->expects(self::once())
            ->method('getReferredEmails')
            ->with(self::identicalTo($this->em), self::identicalTo($newEmail))
            ->willReturn([$threadEmail1, $threadEmail2, $threadEmail3]);

        $this->emailThreadManager->updateThreads([$newEmail]);

        self::assertEquals($thread1, $newEmail->getThread());
        self::assertEquals($thread1, $threadEmail1->getThread());
        self::assertEquals($thread1, $threadEmail2->getThread());
        self::assertEquals($thread2, $threadEmail3->getThread());
    }

    /**
     * @dataProvider updateHeadsDataProvider
     */
    public function testUpdateHeads(array $updatedEmails, array $returnThreadEmails, array $expectedEmails)
    {
        $consecutiveThreadEmails = array_map(
            function (TestEmailEntity $entity, $returnEmails) {
                return $returnEmails ? $this->getThreadEmails($entity) : [];
            },
            $updatedEmails,
            $returnThreadEmails
        );

        $this->emailThreadProvider->expects($this->any())
            ->method('getThreadEmails')
            ->willReturnOnConsecutiveCalls(...$consecutiveThreadEmails);

        $this->emailThreadManager->updateHeads($updatedEmails);
        $this->assertEquals($expectedEmails, $updatedEmails);
    }

    public function updateHeadsDataProvider(): array
    {
        return [
            'new emails are not updated' => [
                [
                    (new TestEmailEntity())
                        ->setHead(false),
                    (new TestEmailEntity())
                        ->setHead(true)
                ],
                [
                    true,
                    true,
                ],
                [
                    (new TestEmailEntity())
                        ->setHead(false),
                    (new TestEmailEntity())
                        ->setHead(true),
                ],
            ],
            'emails without thread are not updated' => [
                [
                    (new TestEmailEntity(4))
                        ->setHead(false),
                    (new TestEmailEntity(4))
                        ->setHead(true),
                ],
                [
                    true,
                    true,
                ],
                [
                    (new TestEmailEntity(4))
                        ->setHead(false),
                    (new TestEmailEntity(4))
                        ->setHead(true),
                ],
            ],
            'updated email should be head' => [
                [
                    (new TestEmailEntity(4))
                        ->setThread(new TestThread(3))
                        ->setHead(false)
                ],
                [
                    true,
                ],
                [
                    (new TestEmailEntity(4))
                        ->setThread(new TestThread(3))
                        ->setHead(true)
                ],
            ],
            'updated email without thread emails are not updated' => [
                [
                    (new TestEmailEntity(4))
                        ->setThread(new TestThread(3))
                        ->setHead(false)
                ],
                [
                    false,
                ],
                [
                    (new TestEmailEntity(4))
                        ->setThread(new TestThread(3))
                        ->setHead(false)
                ],
            ],
        ];
    }

    private function getThreadEmails(object $entity): array
    {
        return array_merge(
            [$entity],
            array_filter($this->fixtures['emails'], function (TestEmailEntity $email) use ($entity) {
                return $entity !== $email && $entity->getThread() == $email->getThread();
            })
        );
    }
}
