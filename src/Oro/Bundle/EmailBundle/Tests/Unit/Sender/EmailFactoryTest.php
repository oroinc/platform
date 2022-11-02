<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sender;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment as EmailAttachmentEntity;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EmailBundle\Provider\ParentMessageIdProvider;
use Oro\Bundle\EmailBundle\Sender\EmailFactory;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Component\Mime\Part\DataPart;

class EmailFactoryTest extends \PHPUnit\Framework\TestCase
{
    private ParentMessageIdProvider|\PHPUnit\Framework\MockObject\MockObject $parentMessageIdProvider;

    private EmailFactory $factory;

    protected function setUp(): void
    {
        $this->parentMessageIdProvider = $this->createMock(ParentMessageIdProvider::class);
        $emailAddressHelper = new EmailAddressHelper();

        $this->factory = new EmailFactory($this->parentMessageIdProvider, $emailAddressHelper);
    }

    /**
     * @dataProvider invalidEmailModelDataProvider
     */
    public function testCreateFromEmailModelThrowsExceptionWhenNotEnoughData(
        EmailModel $emailModel,
        string $expectedMessage
    ): void {
        $this->expectExceptionObject(new \InvalidArgumentException($expectedMessage));

        $this->factory->createFromEmailModel($emailModel);
    }

    public function invalidEmailModelDataProvider(): array
    {
        $emailModelWithoutRecipients = new EmailModel();
        $emailModelWithoutRecipients->setFrom('sender@example.com');

        return [
            'empty sender' => [
                'emailModel' => new EmailModel(),
                'expectedMessage' => 'Sender can not be empty',
            ],
            'empty recipients' => [
                'emailModel' => $emailModelWithoutRecipients,
                'expectedMessage' => 'Recipient can not be empty',
            ],
        ];
    }

    public function testCreateFromEmailModelWhenNoParentMessageId(): void
    {
        $emailModel = $this->getEmailModel();

        $symfonyEmail = $this->factory->createFromEmailModel($emailModel);

        self::assertSymfonyEmailValid($symfonyEmail, $emailModel);

        self::assertEmpty($symfonyEmail->getTextBody());
        self::assertEquals($emailModel->getBody(), $symfonyEmail->getHtmlBody());

        self::assertEmpty($symfonyEmail->getAttachments());
    }

    private function getEmailModel(): EmailModel
    {
        return (new EmailModel())
            ->setFrom('sender@example.com')
            ->setTo(['recipient1 <recipient1@example.com>', 'recipient2 <recipient2@example.com>'])
            ->setCc(['cc1 <cc1@example.com>', 'cc2 <cc2@example.com>'])
            ->setBcc(['bcc1 <bcc1@example.com>', 'bcc2 <bcc2@example.com>'])
            ->setSubject('Email subject')
            ->setType('html')
            ->setBody('<p>Test email.</p>');
    }

    private static function assertSymfonyEmailValid(SymfonyEmail $symfonyEmail, EmailModel $emailModel): void
    {
        self::assertInstanceOf(\DateTimeInterface::class, $symfonyEmail->getDate());

        $senderAddress = SymfonyAddress::create($emailModel->getFrom());
        self::assertEquals([$senderAddress], $symfonyEmail->getFrom());
        self::assertEquals([$senderAddress], $symfonyEmail->getReplyTo());
        self::assertEquals($senderAddress, $symfonyEmail->getReturnPath());

        self::assertEquals(SymfonyAddress::createArray($emailModel->getTo()), $symfonyEmail->getTo());
        self::assertEquals(SymfonyAddress::createArray($emailModel->getCc()), $symfonyEmail->getCc());
        self::assertEquals(SymfonyAddress::createArray($emailModel->getBcc()), $symfonyEmail->getBcc());

        self::assertEquals($emailModel->getSubject(), $symfonyEmail->getSubject());
    }

    public function testCreateFromEmailModelSetsTextBodyWhenTypeText(): void
    {
        $emailModel = ($this->getEmailModel())
            ->setType('text');

        $symfonyEmail = $this->factory->createFromEmailModel($emailModel);

        self::assertSymfonyEmailValid($symfonyEmail, $emailModel);

        self::assertEmpty($symfonyEmail->getHtmlBody());
        self::assertEquals($emailModel->getBody(), $symfonyEmail->getTextBody());

        self::assertEmpty($symfonyEmail->getAttachments());
    }

    public function testCreateFromEmailModelSetsEmptyBodyWhenNoBody(): void
    {
        $emailModel = ($this->getEmailModel())
            ->setBody(null);

        $symfonyEmail = $this->factory->createFromEmailModel($emailModel);

        self::assertSymfonyEmailValid($symfonyEmail, $emailModel);
        self::assertEquals('', $symfonyEmail->getHtmlBody());
        self::assertEmpty($symfonyEmail->getAttachments());
    }

    public function testCreateFromEmailModelSetsReplyToWhenParentMessageId(): void
    {
        $emailModel = $this->getEmailModel();

        $parentMessageId = 'sample/message/id@example.com';
        $this->parentMessageIdProvider
            ->expects(self::once())
            ->method('getParentMessageIdToReply')
            ->with($emailModel)
            ->willReturn('<' . $parentMessageId . '>');

        $symfonyEmail = $this->factory->createFromEmailModel($emailModel);

        self::assertSymfonyEmailValid($symfonyEmail, $emailModel);

        self::assertEmpty($symfonyEmail->getTextBody());
        self::assertEquals($emailModel->getBody(), $symfonyEmail->getHtmlBody());

        self::assertEquals($parentMessageId, $symfonyEmail->getHeaders()->getHeaderBody('References'));
        self::assertEquals($parentMessageId, $symfonyEmail->getHeaders()->getHeaderBody('In-Reply-To'));

        self::assertEmpty($symfonyEmail->getAttachments());
    }

    public function testCreateFromEmailModelWhenAttachments(): void
    {
        $regularEmailAttachmentModel = $this->createEmailAttachmentModel();
        $embeddedEmailAttachmentModel = $this->createEmailAttachmentModel('sample_image');

        $emailModel = ($this->getEmailModel())
            ->setAttachments([new EmailAttachmentModel(), $regularEmailAttachmentModel, $embeddedEmailAttachmentModel]);

        $symfonyEmail = $this->factory->createFromEmailModel($emailModel);

        self::assertSymfonyEmailValid($symfonyEmail, $emailModel);

        $attachments = $symfonyEmail->getAttachments();
        $regularAttachmentContent = $regularEmailAttachmentModel->getEmailAttachment()->getContent();
        self::assertContainsEquals(
            new DataPart(
                base64_decode($regularAttachmentContent->getContent()),
                $regularEmailAttachmentModel->getEmailAttachment()->getFileName(),
                $regularEmailAttachmentModel->getEmailAttachment()->getContentType(),
                $regularAttachmentContent->getContentTransferEncoding()
            ),
            $attachments
        );

        $embeddedEmailAttachmentContent = $embeddedEmailAttachmentModel->getEmailAttachment()->getContent();
        $inlineAttachment = new DataPart(
            base64_decode($embeddedEmailAttachmentContent->getContent()),
            $embeddedEmailAttachmentModel->getEmailAttachment()->getEmbeddedContentId(),
            $embeddedEmailAttachmentModel->getEmailAttachment()->getContentType()
        );
        $inlineAttachment->asInline();
        self::assertContainsEquals($inlineAttachment, $attachments);
    }

    private function createEmailAttachmentModel(string $embeddedContentId = null): EmailAttachmentModel
    {
        $emailAttachmentContent = (new EmailAttachmentContent())
            ->setContent(base64_encode('sample_content'))
            ->setContentTransferEncoding('base64');

        $emailAttachmentEntity = (new EmailAttachmentEntity())
            ->setContent($emailAttachmentContent)
            ->setFileName('sample_filename')
            ->setContentType('sample-media/sample-type')
            ->setEmbeddedContentId($embeddedContentId);

        return (new EmailAttachmentModel())
            ->setType(EmailAttachmentModel::TYPE_UPLOADED)
            ->setEmailAttachment($emailAttachmentEntity);
    }
}
