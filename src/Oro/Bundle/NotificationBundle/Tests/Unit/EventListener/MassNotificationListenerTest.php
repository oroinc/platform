<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\Entity\MassNotification;
use Oro\Bundle\NotificationBundle\Event\NotificationSentEvent;
use Oro\Bundle\NotificationBundle\EventListener\MassNotificationListener;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Component\Mime\RawMessage;

class MassNotificationListenerTest extends \PHPUnit\Framework\TestCase
{
    private EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    private MassNotificationListener $listener;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(MassNotification::class)
            ->willReturn($this->entityManager);

        $this->listener = new MassNotificationListener($doctrine);
    }

    public function testLogMassNotificationWhenTypeNotMass(): void
    {
        $event = new NotificationSentEvent(new SymfonyEmail(), 1, 'sample_type');

        $this->entityManager->expects(self::never())
            ->method(self::anything());

        $this->listener->logMassNotification($event);
    }

    public function testLogMassNotificationWhenTypeNotEmail(): void
    {
        $event = new NotificationSentEvent(
            new RawMessage('sample body'),
            1,
            MassNotificationSender::NOTIFICATION_LOG_TYPE
        );

        $this->entityManager->expects(self::never())
            ->method(self::anything());

        $this->listener->logMassNotification($event);
    }

    /**
     * @dataProvider logMassNotificationDataProvider
     *
     * @param SymfonyEmail $symfonyEmail
     * @param int $sentCount
     * @param MassNotification $massNotification
     */
    public function testLogMassNotification(
        SymfonyEmail $symfonyEmail,
        int $sentCount,
        MassNotification $massNotification
    ): void {
        $event = new NotificationSentEvent($symfonyEmail, $sentCount, MassNotificationSender::NOTIFICATION_LOG_TYPE);

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->willReturnCallback(static function (MassNotification $entity) use ($massNotification) {
                self::assertEquals($massNotification->getSender(), $entity->getSender());
                self::assertEquals($massNotification->getEmail(), $entity->getEmail());
                self::assertEquals($massNotification->getSubject(), $entity->getSubject());
                self::assertEquals($massNotification->getStatus(), $entity->getStatus());
                self::assertEquals($massNotification->getBody(), $entity->getBody());
                self::assertInstanceOf(\DateTimeInterface::class, $entity->getProcessedAt());
                self::assertInstanceOf(\DateTimeInterface::class, $entity->getScheduledAt());
            });

        $this->entityManager->expects(self::once())
            ->method('flush');

        $this->listener->logMassNotification($event);
    }

    public function logMassNotificationDataProvider(): array
    {
        return [
            'status is not success when sentCount is 0' => [
                'symfonyEmail' => (new SymfonyEmail()),
                'sentCount' => 0,
                'massNotification' => (new MassNotification()),
            ],
            'status is success when sentCount > 0' => [
                'symfonyEmail' => (new SymfonyEmail()),
                'sentCount' => 1,
                'massNotification' => (new MassNotification())
                    ->setStatus(MassNotification::STATUS_SUCCESS),
            ],
            'sender is taken from email' => [
                'symfonyEmail' => (new SymfonyEmail())
                    ->from('From1Name <from1@example.com>', 'from2@example.com'),
                'sentCount' => 1,
                'massNotification' => (new MassNotification())
                    ->setStatus(MassNotification::STATUS_SUCCESS)
                    ->setSender('From1Name <from1@example.com>'),
            ],
            'recipient is taken from email' => [
                'symfonyEmail' => (new SymfonyEmail())
                    ->from('From1Name <from1@example.com>', 'from2@example.com')
                    ->to('To1Name <to1@example.com>', 'to1@example.com'),
                'sentCount' => 1,
                'massNotification' => (new MassNotification())
                    ->setStatus(MassNotification::STATUS_SUCCESS)
                    ->setSender('From1Name <from1@example.com>')
                    ->setEmail('To1Name <to1@example.com>'),
            ],
            'subject is taken from email' => [
                'symfonyEmail' => (new SymfonyEmail())
                    ->from('From1Name <from1@example.com>', 'from2@example.com')
                    ->to('To1Name <to1@example.com>', 'to1@example.com')
                    ->subject('Sample subject'),
                'sentCount' => 1,
                'massNotification' => (new MassNotification())
                    ->setStatus(MassNotification::STATUS_SUCCESS)
                    ->setSender('From1Name <from1@example.com>')
                    ->setEmail('To1Name <to1@example.com>')
                    ->setSubject('Sample subject'),
            ],
            'html body is taken from email' => [
                'symfonyEmail' => (new SymfonyEmail())
                    ->from('From1Name <from1@example.com>', 'from2@example.com')
                    ->to('To1Name <to1@example.com>', 'to1@example.com')
                    ->subject('Sample subject')
                    ->text('Sample text body')
                    ->html('Sample html body'),
                'sentCount' => 1,
                'massNotification' => (new MassNotification())
                    ->setStatus(MassNotification::STATUS_SUCCESS)
                    ->setSender('From1Name <from1@example.com>')
                    ->setEmail('To1Name <to1@example.com>')
                    ->setSubject('Sample subject')
                    ->setBody('Sample html body'),
            ],
            'text body is taken from email from html is absent' => [
                'symfonyEmail' => (new SymfonyEmail())
                    ->from('From1Name <from1@example.com>', 'from2@example.com')
                    ->to('To1Name <to1@example.com>', 'to1@example.com')
                    ->subject('Sample subject')
                    ->text('Sample text body'),
                'sentCount' => 1,
                'massNotification' => (new MassNotification())
                    ->setStatus(MassNotification::STATUS_SUCCESS)
                    ->setSender('From1Name <from1@example.com>')
                    ->setEmail('To1Name <to1@example.com>')
                    ->setSubject('Sample subject')
                    ->setBody('Sample text body'),
            ],
        ];
    }
}
