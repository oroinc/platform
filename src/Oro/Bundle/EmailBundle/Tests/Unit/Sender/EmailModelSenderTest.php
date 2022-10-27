<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sender;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Builder\EmailUserFromEmailModelBuilder;
use Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImagesInEmailModelHandler;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Mailer\Envelope\EmailOriginAwareEnvelope;
use Oro\Bundle\EmailBundle\Sender\EmailFactory;
use Oro\Bundle\EmailBundle\Sender\EmailModelSender;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EmailModelSenderTest extends \PHPUnit\Framework\TestCase
{
    private MailerInterface|\PHPUnit\Framework\MockObject\MockObject $mailer;

    private EmbeddedImagesInEmailModelHandler|\PHPUnit\Framework\MockObject\MockObject $embeddedImagesHandler;

    private EmailFactory|\PHPUnit\Framework\MockObject\MockObject $symfonyEmailFactory;

    private EmailUserFromEmailModelBuilder|\PHPUnit\Framework\MockObject\MockObject $emailUserFromEmailModelBuilder;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private EmailModelSender $emailModelSender;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->embeddedImagesHandler = $this->createMock(EmbeddedImagesInEmailModelHandler::class);
        $this->symfonyEmailFactory = $this->createMock(EmailFactory::class);
        $this->emailUserFromEmailModelBuilder = $this->createMock(EmailUserFromEmailModelBuilder::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->emailModelSender = new EmailModelSender(
            $this->mailer,
            $this->embeddedImagesHandler,
            $this->symfonyEmailFactory,
            $this->emailUserFromEmailModelBuilder,
            $this->eventDispatcher
        );
    }

    public function testSendWithoutEmailOrigin(): void
    {
        $emailModel = (new EmailModel())
            ->setContexts(new ArrayCollection([new \stdClass()]));
        $symfonyEmail = (new SymfonyEmail())
            ->from('sender@example.org')
            ->to('recipient@example.org')
            ->date(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $messageId = 'sample/message/id@example.org';
        $symfonyEmail->getHeaders()->addHeader('Message-ID', $messageId);

        $this->symfonyEmailFactory
            ->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel)
            ->willReturn($symfonyEmail);

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with($symfonyEmail);

        $email = new EmailEntity();
        $emailUser = (new EmailUser())
            ->setEmail($email);

        $this->emailUserFromEmailModelBuilder
            ->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel, '<' . $messageId . '>', $symfonyEmail->getDate())
            ->willReturn($emailUser);

        $this->emailUserFromEmailModelBuilder
            ->expects(self::never())
            ->method('setEmailOrigin');

        $this->emailUserFromEmailModelBuilder
            ->expects(self::once())
            ->method('addActivityEntities')
            ->with($emailUser, $emailModel->getContexts());

        $this->emailUserFromEmailModelBuilder
            ->expects(self::once())
            ->method('persistAndFlush');

        $event = new EmailBodyAdded($email);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event, EmailBodyAdded::NAME)
            ->willReturn($event);

        self::assertEquals($emailUser, $this->emailModelSender->send($emailModel, null, true));
    }

    public function testSendWithEmailOrigin(): void
    {
        $emailModel = new EmailModel();
        $symfonyEmail = (new SymfonyEmail())
            ->from('sender@example.org')
            ->to('recipient@example.org')
            ->date(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $messageId = 'sample/message/id@example.org';
        $symfonyEmail->getHeaders()->addHeader('Message-ID', $messageId);

        $this->symfonyEmailFactory
            ->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel)
            ->willReturn($symfonyEmail);

        $emailOrigin = $this->createMock(EmailOrigin::class);

        $envelope = EmailOriginAwareEnvelope::create($symfonyEmail);
        $envelope->setEmailOrigin($emailOrigin);

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with($symfonyEmail, $envelope);

        $email = new EmailEntity();
        $emailUser = (new EmailUser())
            ->setEmail($email);

        $this->emailUserFromEmailModelBuilder
            ->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel, '<' . $messageId . '>', $symfonyEmail->getDate())
            ->willReturn($emailUser);

        $this->emailUserFromEmailModelBuilder
            ->expects(self::once())
            ->method('setEmailOrigin')
            ->with($emailUser, $emailOrigin);

        $this->emailUserFromEmailModelBuilder
            ->expects(self::once())
            ->method('addActivityEntities')
            ->with($emailUser, $emailModel->getContexts());

        $this->emailUserFromEmailModelBuilder
            ->expects(self::once())
            ->method('persistAndFlush');

        $event = new EmailBodyAdded($email);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event, EmailBodyAdded::NAME)
            ->willReturn($event);

        self::assertEquals($emailUser, $this->emailModelSender->send($emailModel, $emailOrigin, true));
    }

    public function testSendWhenNotPersist(): void
    {
        $emailModel = new EmailModel();
        $symfonyEmail = (new SymfonyEmail())
            ->from('sender@example.org')
            ->to('recipient@example.org')
            ->date(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $messageId = 'sample/message/id@example.org';
        $symfonyEmail->getHeaders()->addHeader('Message-ID', $messageId);

        $this->symfonyEmailFactory
            ->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel)
            ->willReturn($symfonyEmail);

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with($symfonyEmail);

        $email = new EmailEntity();
        $emailUser = (new EmailUser())
            ->setEmail($email);

        $this->emailUserFromEmailModelBuilder
            ->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel, '<' . $messageId . '>', $symfonyEmail->getDate())
            ->willReturn($emailUser);

        $this->emailUserFromEmailModelBuilder
            ->expects(self::never())
            ->method('setEmailOrigin');

        $this->emailUserFromEmailModelBuilder
            ->expects(self::never())
            ->method('addActivityEntities');

        $this->emailUserFromEmailModelBuilder
            ->expects(self::never())
            ->method('persistAndFlush');

        $event = new EmailBodyAdded($email);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event, EmailBodyAdded::NAME)
            ->willReturn($event);

        self::assertEquals($emailUser, $this->emailModelSender->send($emailModel, null, false));
    }

    public function testSendExtractsEmbeddedImagesWhenHtmlType(): void
    {
        $emailModel = (new EmailModel())
            ->setType('html');
        $symfonyEmail = (new SymfonyEmail())
            ->from('sender@example.org')
            ->to('recipient@example.org')
            ->date(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $messageId = 'sample/message/id@example.org';
        $symfonyEmail->getHeaders()->addHeader('Message-ID', $messageId);

        $this->embeddedImagesHandler
            ->expects(self::once())
            ->method('handleEmbeddedImages')
            ->with($emailModel);

        $this->symfonyEmailFactory
            ->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel)
            ->willReturn($symfonyEmail);

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with($symfonyEmail);

        $email = new EmailEntity();
        $emailUser = (new EmailUser())
            ->setEmail($email);

        $this->emailUserFromEmailModelBuilder
            ->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel, '<' . $messageId . '>', $symfonyEmail->getDate())
            ->willReturn($emailUser);

        $this->emailUserFromEmailModelBuilder
            ->expects(self::never())
            ->method('setEmailOrigin');

        $this->emailUserFromEmailModelBuilder
            ->expects(self::once())
            ->method('addActivityEntities')
            ->with($emailUser, $emailModel->getContexts());

        $this->emailUserFromEmailModelBuilder
            ->expects(self::once())
            ->method('persistAndFlush');

        $event = new EmailBodyAdded($email);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event, EmailBodyAdded::NAME)
            ->willReturn($event);

        self::assertEquals($emailUser, $this->emailModelSender->send($emailModel));
    }
}
