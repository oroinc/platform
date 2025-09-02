<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Model;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment as EmailAttachmentEntity;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment;
use Oro\Bundle\EmailBundle\Model\EmailTemplateAttachmentModel;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class EmailAttachmentTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors(): void
    {
        $properties = [
            ['id', 123],
            ['type', EmailAttachment::TYPE_UPLOADED],
            ['fileName', 'test.pdf', false],
            ['fileSize', 1024],
            ['modified', '2023-01-01 12:00:00'],
            ['preview', '/path/to/preview.jpg'],
            ['icon', 'fa-file-pdf'],
            ['mimeType', 'application/pdf', false],
            ['errors', ['Error 1', 'Error 2']],
        ];

        self::assertPropertyAccessors(new EmailAttachment(), $properties);
    }

    public function testGetFileNameReturnsStringWhenNull(): void
    {
        $emailAttachment = new EmailAttachment();
        self::assertEquals('', $emailAttachment->getFileName());
    }

    public function testEmailAttachmentEntitySetter(): void
    {
        $emailAttachmentEntity = new EmailAttachmentEntity();
        $emailAttachmentEntity->setFileName('entity-file.txt');

        $emailAttachment = new EmailAttachment();
        $emailAttachment->setEmailAttachment($emailAttachmentEntity);

        self::assertSame($emailAttachmentEntity, $emailAttachment->getEmailAttachment());
        self::assertEquals('entity-file.txt', $emailAttachment->getFileName());
    }

    public function testEmailAttachmentEntitySetterWithNullEntity(): void
    {
        $emailAttachment = new EmailAttachment();
        $emailAttachment->setFileName('original-name.txt');

        self::assertNull($emailAttachment->getEmailAttachment());
        self::assertEquals('original-name.txt', $emailAttachment->getFileName());
    }

    public function testEmailTemplateAttachmentAccessors(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();

        self::assertPropertyAccessors(
            new EmailAttachment(),
            [['emailTemplateAttachment', $emailTemplateAttachment]]
        );
    }

    public function testUploadedFileAccessor(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);

        self::assertPropertyAccessors(
            new EmailAttachment(),
            [['file', $uploadedFile]]
        );
    }

    public function testAddError(): void
    {
        $emailAttachment = new EmailAttachment();

        $emailAttachment->addError('First error');
        $emailAttachment->addError('Second error');

        $expectedErrors = ['First error', 'Second error'];
        self::assertEquals($expectedErrors, $emailAttachment->getErrors());
    }

    public function testConstantsAreCorrect(): void
    {
        self::assertEquals(1, EmailAttachment::TYPE_ATTACHMENT);
        self::assertEquals(2, EmailAttachment::TYPE_EMAIL_ATTACHMENT);
        self::assertEquals(3, EmailAttachment::TYPE_UPLOADED);
        self::assertEquals(4, EmailAttachment::TYPE_EMAIL_TEMPLATE_ATTACHMENT);
    }

    public function testFluentInterface(): void
    {
        $emailAttachment = new EmailAttachment();

        $result = $emailAttachment
            ->setId(1)
            ->setType(EmailAttachment::TYPE_ATTACHMENT)
            ->setFileName('test.txt')
            ->setFileSize(512)
            ->setModified('2023-01-01')
            ->setPreview('preview.jpg')
            ->setIcon('fa-file')
            ->setMimeType('text/plain')
            ->setErrors([]);

        self::assertSame($emailAttachment, $result);
    }

    public function testEmailAttachmentEntityFluentInterface(): void
    {
        $emailAttachmentEntity = new EmailAttachmentEntity();
        $emailAttachment = new EmailAttachment();

        $result = $emailAttachment->setEmailAttachment($emailAttachmentEntity);
        self::assertSame($emailAttachment, $result);
    }

    public function testEmailTemplateAttachmentFluentInterface(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailAttachment = new EmailAttachment();

        $result = $emailAttachment->setEmailTemplateAttachment($emailTemplateAttachment);
        self::assertSame($emailAttachment, $result);
    }

    public function testUploadedFileFluentInterface(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);

        $emailAttachment = new EmailAttachment();

        $result = $emailAttachment->setFile($uploadedFile);
        self::assertSame($emailAttachment, $result);
    }
}
