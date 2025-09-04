<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Factory\EmailAttachmentEntityFromEmailTemplateAttachmentFactory;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EmailBundle\Form\Model\Factory;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateAttachmentModel;
use Oro\Bundle\EmailBundle\Tools\EmailAttachmentTransformer;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EmailAttachmentTransformerTest extends TestCase
{
    private FileManager&MockObject $fileManager;
    private AttachmentManager&MockObject $manager;
    private EmailAttachmentManager&MockObject $emailAttachmentManager;
    private EmailAttachmentEntityFromEmailTemplateAttachmentFactory&MockObject $emailAttachmentEntityFactory;
    private EmailAttachmentTransformer $emailAttachmentTransformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->manager = $this->createMock(AttachmentManager::class);
        $this->emailAttachmentManager = $this->createMock(EmailAttachmentManager::class);
        $this->emailAttachmentEntityFactory =
            $this->createMock(EmailAttachmentEntityFromEmailTemplateAttachmentFactory::class);

        $this->emailAttachmentTransformer = new EmailAttachmentTransformer(
            new Factory(),
            $this->fileManager,
            $this->manager,
            $this->emailAttachmentManager
        );
        $this->emailAttachmentTransformer->setEmailAttachmentEntityFactory($this->emailAttachmentEntityFactory);
    }

    public function testEntityToModel(): void
    {
        $emailAttachment = $this->createMock(EmailAttachment::class);
        $emailAttachment->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $emailAttachment->expects($this->once())
            ->method('getSize')
            ->willReturn(12);

        $emailBody = $this->createMock(EmailBody::class);
        $emailBody->expects($this->once())
            ->method('getCreated')
            ->willReturn('2015-04-13 19:09:32');

        $emailAttachment->expects($this->once())
            ->method('getEmailBody')
            ->willReturn($emailBody);
        $emailAttachment->expects($this->once())
            ->method('getContentType')
            ->willReturn('image/jpeg');

        $this->manager->expects($this->once())
            ->method('isImageType')
            ->willReturn(true);

        $this->manager->expects($this->once())
            ->method('getAttachmentIconClass')
            ->willReturn('fa-class');

        $this->emailAttachmentManager->expects($this->once())
            ->method('getResizedImageUrl')
            ->willReturn('imageurl.jpg');

        $attachmentModel = $this->emailAttachmentTransformer->entityToModel($emailAttachment);

        $this->assertInstanceOf(EmailAttachmentModel::class, $attachmentModel);
        $this->assertEquals(1, $attachmentModel->getId());
        $this->assertEquals(12, $attachmentModel->getFileSize());
        $this->assertEquals('2015-04-13 19:09:32', $attachmentModel->getModified());
        $this->assertEquals(2, $attachmentModel->getType());
        $this->assertEquals($emailAttachment, $attachmentModel->getEmailAttachment());
        $this->assertEquals('imageurl.jpg', $attachmentModel->getPreview());
        $this->assertEquals('fa-class', $attachmentModel->getIcon());
        $this->assertEquals('image/jpeg', $attachmentModel->getMimeType());
        $this->assertEquals(12, $attachmentModel->getFileSize());
    }

    public function testAttachmentEntityToModel(): void
    {
        $file = new File();
        $file->setOriginalFilename('filename.txt');
        $file->setFileSize(100);
        $file->setMimeType('image/jpeg');

        $attachment = new Attachment();
        ReflectionUtil::setId($attachment, 1);
        $attachment->setFile($file);
        $attachment->setCreatedAt(new \DateTime('2015-04-13 19:09:32', new \DateTimeZone('UTC')));

        $this->manager->expects($this->once())
            ->method('isImageType')
            ->willReturn(true);

        $this->manager->expects($this->once())
            ->method('getAttachmentIconClass')
            ->willReturn('fa-class');

        $this->manager->expects($this->once())
            ->method('getResizedImageUrl')
            ->willReturn('imageurl.jpg');

        $attachmentModel = $this->emailAttachmentTransformer->attachmentEntityToModel($attachment);

        $this->assertInstanceOf(EmailAttachmentModel::class, $attachmentModel);
        $this->assertEquals(1, $attachmentModel->getId());
        $this->assertEquals(100, $attachmentModel->getFileSize());
        $this->assertEquals(
            new \DateTime('2015-04-13 19:09:32', new \DateTimeZone('UTC')),
            $attachmentModel->getModified()
        );
        $this->assertEquals(1, $attachmentModel->getType());
        $this->assertEquals(null, $attachmentModel->getEmailAttachment());
        $this->assertEquals('imageurl.jpg', $attachmentModel->getPreview());
        $this->assertEquals('fa-class', $attachmentModel->getIcon());
        $this->assertEquals('image/jpeg', $attachmentModel->getMimeType());
        $this->assertEquals(100, $attachmentModel->getFileSize());
    }

    public function testAttachmentEntityToEntity(): void
    {
        $file = new File();
        $file->setOriginalFilename('filename.txt');
        $file->setFilename('filename');
        $file->setMimeType('text/plain');

        $this->fileManager->expects($this->once())
            ->method('getContent')
            ->with($this->identicalTo($file))
            ->willReturn('content');

        $attachment = new Attachment();
        $attachment->setFile($file);

        $emailAttachment = $this->emailAttachmentTransformer->attachmentEntityToEntity($attachment);

        $this->assertInstanceOf(EmailAttachment::class, $emailAttachment);
        $this->assertEquals(null, $emailAttachment->getId());
        $this->assertInstanceOf(EmailAttachmentContent::class, $emailAttachment->getContent());
        $this->assertEquals(base64_encode('content'), $emailAttachment->getContent()->getContent());
        $this->assertEquals('base64', $emailAttachment->getContent()->getContentTransferEncoding());
        $this->assertEquals($emailAttachment, $emailAttachment->getContent()->getEmailAttachment());
        $this->assertEquals('text/plain', $emailAttachment->getContentType());
        $this->assertEquals('filename.txt', $emailAttachment->getFileName());
    }

    public function testEntityFromUploadedFile(): void
    {
        $fileContent = "test attachment\n";

        $uploadedFile = $this->getMockBuilder(UploadedFile::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([__DIR__ . '/../Fixtures/attachment/test.txt', ''])
            ->getMock();

        $uploadedFile->expects($this->once())
            ->method('getMimeType')
            ->willReturn('text/plain');

        $uploadedFile->expects($this->once())
            ->method('getClientOriginalName')
            ->willReturn('test.txt');

        $uploadedFile->expects($this->once())
            ->method('getRealPath')
            ->willReturn(__DIR__ . '/../Fixtures/attachment/test.txt');

        $emailAttachment = $this->emailAttachmentTransformer->entityFromUploadedFile($uploadedFile);

        $this->assertInstanceOf(EmailAttachment::class, $emailAttachment);
        $content = $emailAttachment->getContent();
        $this->assertEquals(base64_encode($fileContent), $content->getContent());
        $this->assertEquals('base64', $content->getContentTransferEncoding());

        $this->assertEquals('text/plain', $emailAttachment->getContentType());
        $this->assertEquals('test.txt', $emailAttachment->getFileName());
    }

    public function testEntityFromEmailTemplateAttachment(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(123);
        $emailTemplateAttachment->setFilePlaceholder('{{ entity.file }}');

        $templateParams = ['entity' => ['file' => new File()]];

        $expectedEmailAttachment = new EmailAttachment();
        $expectedEmailAttachment->setFileName('test.pdf');
        $expectedEmailAttachment->setContentType('application/pdf');

        $this->emailAttachmentEntityFactory
            ->expects(self::once())
            ->method('createEmailAttachmentEntities')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn([$expectedEmailAttachment]);

        $result = $this->emailAttachmentTransformer
            ->entityFromEmailTemplateAttachment($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertSame($expectedEmailAttachment, $result[0]);
    }

    public function testEntityFromEmailTemplateAttachmentWithMultipleAttachments(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(789);
        $emailTemplateAttachment->setFilePlaceholder('{{ entity.fileCollection }}');

        $templateParams = ['entity' => ['fileCollection' => []]];

        $expectedEmailAttachment1 = new EmailAttachment();
        $expectedEmailAttachment1->setFileName('file1.pdf');
        $expectedEmailAttachment1->setContentType('application/pdf');

        $expectedEmailAttachment2 = new EmailAttachment();
        $expectedEmailAttachment2->setFileName('file2.txt');
        $expectedEmailAttachment2->setContentType('text/plain');

        $expectedAttachments = [$expectedEmailAttachment1, $expectedEmailAttachment2];

        $this->emailAttachmentEntityFactory
            ->expects(self::once())
            ->method('createEmailAttachmentEntities')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn($expectedAttachments);

        $result = $this->emailAttachmentTransformer
            ->entityFromEmailTemplateAttachment($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertCount(2, $result);
        self::assertSame($expectedEmailAttachment1, $result[0]);
        self::assertSame($expectedEmailAttachment2, $result[1]);
    }

    public function testEntityFromEmailTemplateAttachmentWithEmptyResult(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(999);
        $emailTemplateAttachment->setFilePlaceholder('{{ entity.nonExistentFile }}');

        $templateParams = ['entity' => []];

        $this->emailAttachmentEntityFactory
            ->expects(self::once())
            ->method('createEmailAttachmentEntities')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn([]);

        $result = $this->emailAttachmentTransformer
            ->entityFromEmailTemplateAttachment($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testEntityFromEmailTemplateAttachmentWithNullFactory(): void
    {
        // Create transformer without setting factory
        $transformer = new EmailAttachmentTransformer(
            new Factory(),
            $this->fileManager,
            $this->manager,
            $this->emailAttachmentManager
        );

        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(123);
        $emailTemplateAttachment->setFilePlaceholder('{{ entity.file }}');

        $templateParams = ['entity' => ['file' => new File()]];

        $result = $transformer->entityFromEmailTemplateAttachment($emailTemplateAttachment, $templateParams);

        // Verify BC layer returns empty array when factory is null
        self::assertIsArray($result);
        self::assertEmpty($result);
    }
}
