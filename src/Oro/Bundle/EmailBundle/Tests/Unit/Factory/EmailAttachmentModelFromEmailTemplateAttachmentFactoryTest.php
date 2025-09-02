<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Factory;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EmailBundle\Factory\EmailAttachmentModelFromEmailTemplateAttachmentFactory;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Model\Factory;
use Oro\Bundle\EmailBundle\Model\EmailTemplateAttachmentModel;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateAttachmentProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EmailAttachmentModelFromEmailTemplateAttachmentFactoryTest extends TestCase
{
    private Factory&MockObject $emailModelFactory;
    private EmailTemplateAttachmentProcessor&MockObject $emailTemplateAttachmentProcessor;
    private AttachmentManager&MockObject $attachmentManager;
    private EmailAttachmentModelFromEmailTemplateAttachmentFactory $factory;

    protected function setUp(): void
    {
        $this->emailModelFactory = $this->createMock(Factory::class);
        $this->emailTemplateAttachmentProcessor = $this->createMock(EmailTemplateAttachmentProcessor::class);
        $this->attachmentManager = $this->createMock(AttachmentManager::class);

        $this->factory = new EmailAttachmentModelFromEmailTemplateAttachmentFactory(
            $this->emailModelFactory,
            $this->emailTemplateAttachmentProcessor,
            $this->attachmentManager
        );
    }

    public function testCreateEmailAttachmentModelSuccess(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(123);
        $emailTemplateAttachment->setFilePlaceholder('{{ entity.file }}');

        $processedAttachment = new EmailTemplateAttachmentModel();
        $processedAttachment->setId(123);

        $file = new File();
        $file->setFilename('test.txt');
        $file->setOriginalFilename('original_test.txt');
        $file->setMimeType('text/plain');
        $file->setFileSize(1024);
        $file->setCreatedAt(new \DateTime('2023-01-01 12:00:00'));
        $processedAttachment->setFile($file);

        $emailAttachment = new EmailAttachment();
        $templateParams = ['entity' => ['file' => $file]];

        $this->emailModelFactory
            ->expects(self::once())
            ->method('getEmailAttachment')
            ->willReturn($emailAttachment);

        $this->emailTemplateAttachmentProcessor
            ->expects(self::once())
            ->method('processAttachment')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn($processedAttachment);

        $this->attachmentManager
            ->expects(self::once())
            ->method('getAttachmentIconClass')
            ->with($file)
            ->willReturn('fa-file-text');

        $this->attachmentManager
            ->expects(self::once())
            ->method('isImageType')
            ->with('text/plain')
            ->willReturn(false);

        $result = $this->factory->createEmailAttachmentModels($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertArrayHasKey(0, $result);

        $emailAttachmentModel = $result[0];
        self::assertSame($emailAttachment, $emailAttachmentModel);
        self::assertSame(123, $emailAttachmentModel->getId());
        self::assertSame(EmailAttachment::TYPE_EMAIL_TEMPLATE_ATTACHMENT, $emailAttachmentModel->getType());
        self::assertSame($emailTemplateAttachment, $emailAttachmentModel->getEmailTemplateAttachment());
        self::assertSame('original_test.txt', $emailAttachmentModel->getFileName());
        self::assertSame('text/plain', $emailAttachmentModel->getMimeType());
        self::assertSame(1024, $emailAttachmentModel->getFileSize());
        self::assertSame('2023-01-01 12:00:00', $emailAttachmentModel->getModified()->format('Y-m-d H:i:s'));
        self::assertSame('fa-file-text', $emailAttachmentModel->getIcon());
        self::assertNull($emailAttachmentModel->getPreview());
    }

    public function testCreateEmailAttachmentModelWithImageFile(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(456);

        $processedAttachment = new EmailTemplateAttachmentModel();
        $processedAttachment->setId(456);

        $file = new File();
        $file->setFilename('image.jpg');
        $file->setOriginalFilename('original_image.jpg');
        $file->setMimeType('image/jpeg');
        $file->setFileSize(2048);
        $file->setCreatedAt(new \DateTime('2023-02-01 14:30:00'));
        $processedAttachment->setFile($file);

        $emailAttachment = new EmailAttachment();
        $templateParams = [];

        $this->emailModelFactory
            ->expects(self::once())
            ->method('getEmailAttachment')
            ->willReturn($emailAttachment);

        $this->emailTemplateAttachmentProcessor
            ->expects(self::once())
            ->method('processAttachment')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn($processedAttachment);

        $this->attachmentManager
            ->expects(self::once())
            ->method('getAttachmentIconClass')
            ->with($file)
            ->willReturn('fa-file-image');

        $this->attachmentManager
            ->expects(self::once())
            ->method('isImageType')
            ->with('image/jpeg')
            ->willReturn(true);

        $this->attachmentManager
            ->expects(self::once())
            ->method('getResizedImageUrl')
            ->with(
                $file,
                AttachmentManager::THUMBNAIL_WIDTH,
                AttachmentManager::THUMBNAIL_HEIGHT
            )
            ->willReturn('/path/to/thumbnail.jpg');

        $result = $this->factory->createEmailAttachmentModels($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertArrayHasKey(0, $result);

        $emailAttachmentModel = $result[0];
        self::assertSame($emailAttachment, $emailAttachmentModel);
        self::assertSame(456, $emailAttachmentModel->getId());
        self::assertSame(EmailAttachment::TYPE_EMAIL_TEMPLATE_ATTACHMENT, $emailAttachmentModel->getType());
        self::assertSame('original_image.jpg', $emailAttachmentModel->getFileName());
        self::assertSame('image/jpeg', $emailAttachmentModel->getMimeType());
        self::assertSame(2048, $emailAttachmentModel->getFileSize());
        self::assertSame('2023-02-01 14:30:00', $emailAttachmentModel->getModified()->format('Y-m-d H:i:s'));
        self::assertSame('fa-file-image', $emailAttachmentModel->getIcon());
        self::assertSame('/path/to/thumbnail.jpg', $emailAttachmentModel->getPreview());
    }

    public function testCreateEmailAttachmentModelFromFileItems(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(789);
        $emailTemplateAttachment->setFilePlaceholder('{{ entity.fileCollection }}');

        $processedAttachment = new EmailTemplateAttachmentModel();
        $processedAttachment->setId(789);

        $file1 = new File();
        $file1->setFilename('file1.txt');
        $file1->setOriginalFilename('original_file1.txt');
        $file1->setMimeType('text/plain');
        $file1->setFileSize(1024);
        $file1->setCreatedAt(new \DateTime('2023-01-01 12:00:00'));

        $file2 = new File();
        $file2->setFilename('file2.jpg');
        $file2->setOriginalFilename('original_file2.jpg');
        $file2->setMimeType('image/jpeg');
        $file2->setFileSize(2048);
        $file2->setCreatedAt(new \DateTime('2023-01-02 14:00:00'));

        $fileItem1 = new FileItem();
        $fileItem1->setFile($file1);
        $fileItem2 = new FileItem();
        $fileItem2->setFile($file2);

        $processedAttachment->addFileItem($fileItem1);
        $processedAttachment->addFileItem($fileItem2);

        $emailAttachment1 = new EmailAttachment();
        $emailAttachment2 = new EmailAttachment();
        $templateParams = [];

        $this->emailModelFactory
            ->expects(self::once())
            ->method('getEmailAttachment')
            ->willReturn($emailAttachment1);

        $this->emailTemplateAttachmentProcessor
            ->expects(self::once())
            ->method('processAttachment')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn($processedAttachment);

        $this->attachmentManager
            ->expects(self::exactly(2))
            ->method('getAttachmentIconClass')
            ->willReturnMap([
                [$file1, 'fa-file-text'],
                [$file2, 'fa-file-image'],
            ]);

        $this->attachmentManager
            ->expects(self::exactly(2))
            ->method('isImageType')
            ->willReturnMap([
                ['text/plain', false],
                ['image/jpeg', true],
            ]);

        $this->attachmentManager
            ->expects(self::once())
            ->method('getResizedImageUrl')
            ->with(
                $file2,
                AttachmentManager::THUMBNAIL_WIDTH,
                AttachmentManager::THUMBNAIL_HEIGHT
            )
            ->willReturn('/path/to/thumbnail2.jpg');

        $result = $this->factory->createEmailAttachmentModels($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertCount(2, $result);
        self::assertArrayHasKey(0, $result);
        self::assertArrayHasKey(1, $result);

        // Check first attachment
        $emailAttachmentModel1 = $result[0];
        self::assertSame(789, $emailAttachmentModel1->getId());
        self::assertSame(EmailAttachment::TYPE_EMAIL_TEMPLATE_ATTACHMENT, $emailAttachmentModel1->getType());
        self::assertSame('original_file1.txt', $emailAttachmentModel1->getFileName());
        self::assertSame('text/plain', $emailAttachmentModel1->getMimeType());
        self::assertSame(1024, $emailAttachmentModel1->getFileSize());
        self::assertSame('fa-file-text', $emailAttachmentModel1->getIcon());
        self::assertNull($emailAttachmentModel1->getPreview());

        // Check second attachment
        $emailAttachmentModel2 = $result[1];
        self::assertSame(789, $emailAttachmentModel2->getId());
        self::assertSame(EmailAttachment::TYPE_EMAIL_TEMPLATE_ATTACHMENT, $emailAttachmentModel2->getType());
        self::assertSame('original_file2.jpg', $emailAttachmentModel2->getFileName());
        self::assertSame('image/jpeg', $emailAttachmentModel2->getMimeType());
        self::assertSame(2048, $emailAttachmentModel2->getFileSize());
        self::assertSame('fa-file-image', $emailAttachmentModel2->getIcon());
        self::assertSame('/path/to/thumbnail2.jpg', $emailAttachmentModel2->getPreview());
    }

    public function testCreateEmailAttachmentModelWhenProcessedAttachmentIsNull(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(789);
        $emailTemplateAttachment->setFilePlaceholder('{{ entity.nonExistentFile }}');

        $emailAttachment = new EmailAttachment();
        $templateParams = ['entity' => []];

        $this->emailModelFactory
            ->expects(self::once())
            ->method('getEmailAttachment')
            ->willReturn($emailAttachment);

        $this->emailTemplateAttachmentProcessor
            ->expects(self::once())
            ->method('processAttachment')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn(null);

        $this->attachmentManager
            ->expects(self::never())
            ->method('getAttachmentIconClass');

        $result = $this->factory->createEmailAttachmentModels($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testCreateEmailAttachmentModelWhenFileIsNull(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(101);

        $processedAttachment = new EmailTemplateAttachmentModel();
        $processedAttachment->setId(101);
        $processedAttachment->setFile(null);

        $emailAttachment = new EmailAttachment();
        $templateParams = [];

        $this->emailModelFactory
            ->expects(self::once())
            ->method('getEmailAttachment')
            ->willReturn($emailAttachment);

        $this->emailTemplateAttachmentProcessor
            ->expects(self::once())
            ->method('processAttachment')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn($processedAttachment);

        $this->attachmentManager
            ->expects(self::never())
            ->method('getAttachmentIconClass');

        $result = $this->factory->createEmailAttachmentModels($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testCreateEmailAttachmentModelFromFileItemsWithNullFile(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(999);

        $processedAttachment = new EmailTemplateAttachmentModel();
        $processedAttachment->setId(999);

        $file = new File();
        $file->setFilename('file.txt');
        $file->setOriginalFilename('original_file.txt');
        $file->setMimeType('text/plain');
        $file->setFileSize(1024);
        $file->setCreatedAt(new \DateTime('2023-01-01 12:00:00'));

        $fileItem1 = new FileItem();
        $fileItem1->setFile(null);
        $fileItem2 = new FileItem();
        $fileItem2->setFile($file);

        $processedAttachment->addFileItem($fileItem1);
        $processedAttachment->addFileItem($fileItem2);

        $emailAttachment = new EmailAttachment();
        $templateParams = [];

        $this->emailModelFactory
            ->expects(self::once())
            ->method('getEmailAttachment')
            ->willReturn($emailAttachment);

        $this->emailTemplateAttachmentProcessor
            ->expects(self::once())
            ->method('processAttachment')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn($processedAttachment);

        $this->attachmentManager
            ->expects(self::once())
            ->method('getAttachmentIconClass')
            ->with($file)
            ->willReturn('fa-file-text');

        $this->attachmentManager
            ->expects(self::once())
            ->method('isImageType')
            ->with('text/plain')
            ->willReturn(false);

        $result = $this->factory->createEmailAttachmentModels($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertArrayHasKey(1, $result);

        $emailAttachmentModel = $result[1];
        self::assertSame('original_file.txt', $emailAttachmentModel->getFileName());
        self::assertSame('text/plain', $emailAttachmentModel->getMimeType());
    }
}
