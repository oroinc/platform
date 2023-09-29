<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Component\Testing\ReflectionUtil;

class EmailThreadManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailThreadProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $emailThreadProvider;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var EmailThreadManager */
    private $emailThreadManager;

    protected function setUp(): void
    {
        $this->emailThreadProvider = $this->createMock(EmailThreadProvider::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Email::class)
            ->willReturn($this->em);

        $this->emailThreadManager = new EmailThreadManager($this->emailThreadProvider, $doctrine);
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

        $this->emailThreadManager->updateThreads([$newEmail]);

        self::assertEquals($thread1, $newEmail->getThread());
        self::assertEquals($thread1, $threadEmail1->getThread());
        self::assertEquals($thread1, $threadEmail2->getThread());
        self::assertEquals($thread2, $threadEmail3->getThread());
    }

    public function testUpdateHeadsForNewEmails(): void
    {
        $newEmail1 = $this->getEmail(null, false);
        $newEmail2 = $this->getEmail(null, true);

        $this->emailThreadProvider->expects(self::never())
            ->method('getThreadEmails');

        $this->emailThreadManager->updateHeads([$newEmail1, $newEmail2]);

        self::assertFalse($newEmail1->isHead());
        self::assertTrue($newEmail2->isHead());
    }

    public function testUpdateHeadsForEmailsWithoutThread(): void
    {
        $updatedEmail1 = $this->getEmail(1, false);
        $updatedEmail2 = $this->getEmail(1, true);

        $this->emailThreadProvider->expects(self::never())
            ->method('getThreadEmails');

        $this->emailThreadManager->updateHeads([$updatedEmail1, $updatedEmail2]);

        self::assertFalse($updatedEmail1->isHead());
        self::assertTrue($updatedEmail2->isHead());
    }

    public function testUpdateHeadsWhenUpdatedEmailShouldBeHead(): void
    {
        $updatedEmail = $this->getEmail(4, false, $this->getThread(3));
        $threadEmail = $this->getEmail(1, true, $this->getThread(3));

        $this->emailThreadProvider->expects(self::once())
            ->method('getThreadEmails')
            ->with(self::identicalTo($this->em), self::identicalTo($updatedEmail))
            ->willReturn([$updatedEmail, $threadEmail]);

        $this->emailThreadManager->updateHeads([$updatedEmail]);

        self::assertTrue($updatedEmail->isHead());
        self::assertFalse($threadEmail->isHead());
    }

    public function testUpdateHeadsForUpdatedEmailWithoutThreadEmails(): void
    {
        $updatedEmail = $this->getEmail(4, false, $this->getThread(3));
        $threadEmail = $this->getEmail(1, false, $this->getThread(3));

        $this->emailThreadProvider->expects(self::once())
            ->method('getThreadEmails')
            ->with(self::identicalTo($this->em), self::identicalTo($updatedEmail))
            ->willReturn([$threadEmail, $updatedEmail]);

        $this->emailThreadManager->updateHeads([$updatedEmail]);

        self::assertFalse($updatedEmail->isHead());
        self::assertTrue($threadEmail->isHead());
    }
}
