<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sender;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Builder\EmailUserFromEmailModelBuilder;
use Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImagesInEmailModelHandler;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\EmailBundle\EventListener\EntityListener;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Mailer\Envelope\EmailOriginAwareEnvelope;
use Oro\Bundle\EmailBundle\Sender\EmailFactory;
use Oro\Bundle\EmailBundle\Sender\EmailModelSender;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EmailModelSenderTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private EmbeddedImagesInEmailModelHandler&MockObject $embeddedImagesHandler;
    private EmailFactory&MockObject $symfonyEmailFactory;
    private EmailUserFromEmailModelBuilder&MockObject $emailUserFromEmailModelBuilder;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private EntityListener&MockObject $emailEntityListener;
    private EmailModelSender $emailModelSender;

    #[\Override]
    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->embeddedImagesHandler = $this->createMock(EmbeddedImagesInEmailModelHandler::class);
        $this->symfonyEmailFactory = $this->createMock(EmailFactory::class);
        $this->emailUserFromEmailModelBuilder = $this->createMock(EmailUserFromEmailModelBuilder::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->emailEntityListener = $this->createMock(EntityListener::class);

        $this->emailModelSender = new EmailModelSender(
            $this->mailer,
            $this->embeddedImagesHandler,
            $this->symfonyEmailFactory,
            $this->emailUserFromEmailModelBuilder,
            $this->eventDispatcher,
            $this->emailEntityListener
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
        $symfonyEmail->getHeaders()->addTextHeader('X-Oro-Message-ID', '<' . $messageId . '>');

        $this->symfonyEmailFactory->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel)
            ->willReturn($symfonyEmail);

        $this->mailer->expects(self::once())
            ->method('send')
            ->with($symfonyEmail);

        $email = new EmailEntity();
        $emailUser = (new EmailUser())
            ->setEmail($email);

        $this->emailUserFromEmailModelBuilder->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel, '<' . $messageId . '>', $symfonyEmail->getDate())
            ->willReturn($emailUser);

        $this->emailUserFromEmailModelBuilder->expects(self::never())
            ->method('setEmailOrigin');

        $this->emailUserFromEmailModelBuilder->expects(self::once())
            ->method('addActivityEntities')
            ->with($emailUser, $emailModel->getContexts());

        $this->emailEntityListener->expects(self::never())
            ->method('skipUpdateActivities');

        $this->emailUserFromEmailModelBuilder->expects(self::once())
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
        $symfonyEmail->getHeaders()->addTextHeader('X-Oro-Message-ID', '<' . $messageId . '>');

        $this->symfonyEmailFactory->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel)
            ->willReturn($symfonyEmail);

        $emailOrigin = $this->createMock(EmailOrigin::class);

        $envelope = EmailOriginAwareEnvelope::create($symfonyEmail);
        $envelope->setEmailOrigin($emailOrigin);

        $this->mailer->expects(self::once())
            ->method('send')
            ->with($symfonyEmail, $envelope);

        $email = new EmailEntity();
        $emailUser = (new EmailUser())
            ->setEmail($email);

        $this->emailUserFromEmailModelBuilder->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel, '<' . $messageId . '>', $symfonyEmail->getDate())
            ->willReturn($emailUser);

        $this->emailUserFromEmailModelBuilder->expects(self::once())
            ->method('setEmailOrigin')
            ->with($emailUser, $emailOrigin);

        $this->emailUserFromEmailModelBuilder->expects(self::once())
            ->method('addActivityEntities')
            ->with($emailUser, $emailModel->getContexts());

        $this->emailEntityListener->expects(self::never())
            ->method('skipUpdateActivities');

        $this->emailUserFromEmailModelBuilder->expects(self::once())
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
        $symfonyEmail->getHeaders()->addTextHeader('X-Oro-Message-ID', '<' . $messageId . '>');

        $this->symfonyEmailFactory->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel)
            ->willReturn($symfonyEmail);

        $this->mailer->expects(self::once())
            ->method('send')
            ->with($symfonyEmail);

        $email = new EmailEntity();
        $emailUser = (new EmailUser())
            ->setEmail($email);

        $this->emailUserFromEmailModelBuilder->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel, '<' . $messageId . '>', $symfonyEmail->getDate())
            ->willReturn($emailUser);

        $this->emailUserFromEmailModelBuilder->expects(self::never())
            ->method('setEmailOrigin');

        $this->emailUserFromEmailModelBuilder->expects(self::never())
            ->method('addActivityEntities');

        $this->emailEntityListener->expects(self::never())
            ->method('skipUpdateActivities');

        $this->emailUserFromEmailModelBuilder->expects(self::never())
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
        $symfonyEmail->getHeaders()->addTextHeader('X-Oro-Message-ID', '<' . $messageId . '>');

        $this->embeddedImagesHandler->expects(self::once())
            ->method('handleEmbeddedImages')
            ->with($emailModel);

        $this->symfonyEmailFactory->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel)
            ->willReturn($symfonyEmail);

        $this->mailer->expects(self::once())
            ->method('send')
            ->with($symfonyEmail);

        $email = new EmailEntity();
        $emailUser = (new EmailUser())
            ->setEmail($email);

        $this->emailUserFromEmailModelBuilder->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel, '<' . $messageId . '>', $symfonyEmail->getDate())
            ->willReturn($emailUser);

        $this->emailUserFromEmailModelBuilder->expects(self::never())
            ->method('setEmailOrigin');

        $this->emailUserFromEmailModelBuilder->expects(self::once())
            ->method('addActivityEntities')
            ->with($emailUser, $emailModel->getContexts());

        $this->emailEntityListener->expects(self::never())
            ->method('skipUpdateActivities');

        $this->emailUserFromEmailModelBuilder->expects(self::once())
            ->method('persistAndFlush');

        $event = new EmailBodyAdded($email);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event, EmailBodyAdded::NAME)
            ->willReturn($event);

        self::assertEquals($emailUser, $this->emailModelSender->send($emailModel));
    }

    public function testSendWhenUpdateEmptyContextsIsNotAllowed(): void
    {
        $emailModel = new EmailModel();
        $emailModel->setAllowToUpdateEmptyContexts(false);
        $symfonyEmail = (new SymfonyEmail())
            ->from('sender@example.org')
            ->to('recipient@example.org')
            ->date(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $messageId = 'sample/message/id@example.org';
        $symfonyEmail->getHeaders()->addTextHeader('X-Oro-Message-ID', '<' . $messageId . '>');

        $this->symfonyEmailFactory->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel)
            ->willReturn($symfonyEmail);

        $emailOrigin = $this->createMock(EmailOrigin::class);

        $envelope = EmailOriginAwareEnvelope::create($symfonyEmail);
        $envelope->setEmailOrigin($emailOrigin);

        $this->mailer->expects(self::once())
            ->method('send')
            ->with($symfonyEmail, $envelope);

        $email = new EmailEntity();
        $emailUser = (new EmailUser())
            ->setEmail($email);

        $this->emailUserFromEmailModelBuilder->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel, '<' . $messageId . '>', $symfonyEmail->getDate())
            ->willReturn($emailUser);

        $this->emailUserFromEmailModelBuilder->expects(self::once())
            ->method('setEmailOrigin')
            ->with($emailUser, $emailOrigin);

        $this->emailUserFromEmailModelBuilder->expects(self::once())
            ->method('addActivityEntities')
            ->with($emailUser, $emailModel->getContexts());

        $this->emailEntityListener->expects(self::once())
            ->method('skipUpdateActivities')
            ->with(self::identicalTo($email));

        $this->emailUserFromEmailModelBuilder->expects(self::once())
            ->method('persistAndFlush');

        $event = new EmailBodyAdded($email);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event, EmailBodyAdded::NAME)
            ->willReturn($event);

        self::assertEquals($emailUser, $this->emailModelSender->send($emailModel, $emailOrigin, true));
    }

    /**
     * @dataProvider getSendAddDoctypeWhenHtmlTypeDataProvider
     */
    public function testSendAddDoctypeWhenHtmlType(?string $content, ?string $expectedContent): void
    {
        $emailModel = (new EmailModel())
            ->setType('html')
            ->setBody($content);
        $symfonyEmail = (new SymfonyEmail())
            ->from('sender@example.org')
            ->to('recipient@example.org')
            ->date(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $messageId = 'sample/message/id@example.org';
        $symfonyEmail->getHeaders()->addTextHeader('X-Oro-Message-ID', '<' . $messageId . '>');

        $this->embeddedImagesHandler->expects(self::once())
            ->method('handleEmbeddedImages')
            ->with($emailModel);

        $this->symfonyEmailFactory->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel)
            ->willReturn($symfonyEmail);

        $this->mailer->expects(self::once())
            ->method('send')
            ->with($symfonyEmail);

        $email = new EmailEntity();
        $emailUser = (new EmailUser())
            ->setEmail($email);

        $this->emailUserFromEmailModelBuilder->expects(self::once())
            ->method('createFromEmailModel')
            ->with($emailModel, '<' . $messageId . '>', $symfonyEmail->getDate())
            ->willReturn($emailUser);

        $this->emailUserFromEmailModelBuilder->expects(self::never())
            ->method('setEmailOrigin');

        $this->emailUserFromEmailModelBuilder->expects(self::once())
            ->method('addActivityEntities')
            ->with($emailUser, $emailModel->getContexts());

        $this->emailEntityListener->expects(self::never())
            ->method('skipUpdateActivities');

        $this->emailUserFromEmailModelBuilder->expects(self::once())
            ->method('persistAndFlush');

        $event = new EmailBodyAdded($email);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event, EmailBodyAdded::NAME)
            ->willReturn($event);

        self::assertEquals($emailUser, $this->emailModelSender->send($emailModel));
        self::assertEquals($expectedContent, $emailModel->getBody());
    }

    public function getSendAddDoctypeWhenHtmlTypeDataProvider(): array
    {
        $content = '<p>Bar</p>';
        $doctype = '<!DOCTYPE HTML>';
        $notDoctype = '!DOCTYPE HTML';
        $doctypeHtml5 = '<!DOCTYPE html>' . $content;
        $doctypeHTML5 = '<!DOCTYPE HTML>' . $content;
        $doctypeHTML4 = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">'
            . $content;
        $doctypeXHTML10 = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" '
            . '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . $content;
        $doctypeXHTML11 = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" '
            . '"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">' . $content;
        $doctypeInTheMiddle = $content . '<!DOCTYPE HTML>' . $content;
        $doctypeInTheEnd = $content . '<!DOCTYPE HTML>';

        return [
            [$content, '<!DOCTYPE HTML>' . $content],
            [$doctypeInTheMiddle, '<!DOCTYPE HTML>' . $doctypeInTheMiddle],
            [$doctypeInTheEnd, '<!DOCTYPE HTML>' . $doctypeInTheEnd],
            [$notDoctype, '<!DOCTYPE HTML>' . $notDoctype],
            [null, null],
            [$doctype, $doctype],
            [$doctypeHtml5, $doctypeHtml5],
            [$doctypeHTML5, $doctypeHTML5],
            [$doctypeHTML4, $doctypeHTML4],
            [$doctypeXHTML10, $doctypeXHTML10],
            [$doctypeXHTML11, $doctypeXHTML11],
        ];
    }
}
