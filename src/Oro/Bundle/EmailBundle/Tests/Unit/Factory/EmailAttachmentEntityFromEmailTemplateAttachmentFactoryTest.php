<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Gaufrette\Exception\FileNotFound;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment as EmailAttachmentEntity;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Factory\EmailAttachmentEntityFromEmailTemplateAttachmentFactory;
use Oro\Bundle\EmailBundle\Model\EmailTemplateAttachmentModel;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateAttachmentProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EmailAttachmentEntityFromEmailTemplateAttachmentFactoryTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private EmailEntityBuilder&MockObject $emailEntityBuilder;
    private EmailTemplateAttachmentProcessor&MockObject $emailTemplateAttachmentProcessor;
    private FileManager&MockObject $fileManager;
    private EmailAttachmentEntityFromEmailTemplateAttachmentFactory $factory;

    protected function setUp(): void
    {
        $this->emailEntityBuilder = $this->createMock(EmailEntityBuilder::class);
        $this->emailTemplateAttachmentProcessor = $this->createMock(EmailTemplateAttachmentProcessor::class);
        $this->fileManager = $this->createMock(FileManager::class);

        $this->factory = new EmailAttachmentEntityFromEmailTemplateAttachmentFactory(
            $this->emailEntityBuilder,
            $this->emailTemplateAttachmentProcessor,
            $this->fileManager
        );

        $this->setUpLoggerMock($this->factory);
    }

    public function testCreateEmailAttachmentEntityWhenFile(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(123);
        $emailTemplateAttachment->setFilePlaceholder('entity.avatar');

        $processedAttachment = new EmailTemplateAttachmentModel();
        $processedAttachment->setId(123);

        $file = new File();
        $file->setFilename('test.txt');
        $file->setOriginalFilename('test.txt');
        $file->setMimeType('text/plain');
        $processedAttachment->setFile($file);

        $emailAttachment = new EmailAttachmentEntity();
        $emailAttachmentContent = new EmailAttachmentContent();
        $templateParams = ['param1' => 'value1'];
        $fileContent = 'file content';

        $this->emailTemplateAttachmentProcessor
            ->expects(self::once())
            ->method('processAttachment')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn($processedAttachment);

        $this->fileManager
            ->expects(self::once())
            ->method('getFileContent')
            ->with('test.txt')
            ->willReturn($fileContent);

        $this->emailEntityBuilder
            ->expects(self::once())
            ->method('attachment')
            ->with('test.txt', 'text/plain')
            ->willReturn($emailAttachment);

        $this->emailEntityBuilder
            ->expects(self::once())
            ->method('attachmentContent')
            ->with(base64_encode($fileContent), 'base64')
            ->willReturn($emailAttachmentContent);

        $result = $this->factory->createEmailAttachmentEntities($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertSame($emailAttachment, $result[0]);
        self::assertSame($emailAttachmentContent, $emailAttachment->getContent());
    }

    public function testCreateEmailAttachmentEntityWhenFileItems(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setFilePlaceholder('entity.fileCollection');

        $processedAttachment = new EmailTemplateAttachmentModel();

        $file1 = new File();
        $file1->setFilename('file1.txt');
        $file1->setOriginalFilename('file1.txt');
        $file1->setMimeType('text/plain');

        $file2 = new File();
        $file2->setFilename('file2.txt');
        $file2->setOriginalFilename('file2.txt');
        $file2->setMimeType('text/plain');

        $fileItem1 = new FileItem();
        $fileItem1->setFile($file1);
        $fileItem2 = new FileItem();
        $fileItem2->setFile($file2);

        $fileItems = new ArrayCollection([$fileItem1, $fileItem2]);
        $processedAttachment->addFileItem($fileItem1);
        $processedAttachment->addFileItem($fileItem2);

        $emailAttachment1 = new EmailAttachmentEntity();
        $emailAttachment2 = new EmailAttachmentEntity();
        $emailAttachmentContent1 = new EmailAttachmentContent();
        $emailAttachmentContent2 = new EmailAttachmentContent();
        $templateParams = ['param1' => 'value1'];
        $fileContent = 'file content';

        $this->emailTemplateAttachmentProcessor
            ->expects(self::once())
            ->method('processAttachment')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn($processedAttachment);

        $this->fileManager
            ->expects(self::exactly(2))
            ->method('getFileContent')
            ->willReturnMap([
                ['file1.txt', true, $fileContent],
                ['file2.txt', true, $fileContent],
            ]);

        $this->emailEntityBuilder
            ->expects(self::exactly(2))
            ->method('attachment')
            ->willReturnMap([
                ['file1.txt', 'text/plain', $emailAttachment1],
                ['file2.txt', 'text/plain', $emailAttachment2],
            ]);

        $this->emailEntityBuilder
            ->expects(self::exactly(2))
            ->method('attachmentContent')
            ->with(base64_encode($fileContent), 'base64')
            ->willReturnOnConsecutiveCalls($emailAttachmentContent1, $emailAttachmentContent2);

        $result = $this->factory->createEmailAttachmentEntities($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertCount(2, $result);
        self::assertSame($emailAttachment1, $result[0]);
        self::assertSame($emailAttachment2, $result[1]);
    }

    public function testCreateEmailAttachmentEntityWhenProcessedAttachmentIsNull(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setFilePlaceholder('entity.avatar');
        $templateParams = ['param1' => 'value1'];

        $this->emailTemplateAttachmentProcessor
            ->expects(self::once())
            ->method('processAttachment')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn(null);

        $this->emailEntityBuilder
            ->expects(self::never())
            ->method('attachment');

        $result = $this->factory->createEmailAttachmentEntities($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testCreateEmailAttachmentEntityWhenFileIsNull(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setFilePlaceholder('entity.avatar');

        $processedAttachment = new EmailTemplateAttachmentModel();
        $processedAttachment->setFile(null);

        $templateParams = ['param1' => 'value1'];

        $this->emailTemplateAttachmentProcessor
            ->expects(self::once())
            ->method('processAttachment')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn($processedAttachment);

        $this->emailEntityBuilder
            ->expects(self::never())
            ->method('attachment');

        $result = $this->factory->createEmailAttachmentEntities($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testCreateEmailAttachmentEntityWhenFileContentNotFound(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setFilePlaceholder('entity.avatar');

        $processedAttachment = new EmailTemplateAttachmentModel();
        $file = new File();
        $file->setFilename('test.txt');
        $processedAttachment->setFile($file);

        $templateParams = ['param1' => 'value1'];
        $exception = new FileNotFound('File not found');

        $this->emailTemplateAttachmentProcessor
            ->expects(self::once())
            ->method('processAttachment')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn($processedAttachment);

        $this->fileManager
            ->expects(self::once())
            ->method('getFileContent')
            ->with('test.txt')
            ->willThrowException($exception);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Failed to get content of the file "{filename}" of the email template attachment: {message}.',
                [
                    'filename' => 'test.txt',
                    'message' => $exception->getMessage(),
                    'file' => $file,
                    'exception' => $exception,
                ]
            );

        $this->emailEntityBuilder
            ->expects(self::never())
            ->method('attachment');

        $result = $this->factory->createEmailAttachmentEntities($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testCreateEmailAttachmentEntityWhenFileContentIsEmpty(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setFilePlaceholder('entity.avatar');

        $processedAttachment = new EmailTemplateAttachmentModel();
        $file = new File();
        $file->setFilename('test.txt');
        $processedAttachment->setFile($file);

        $templateParams = ['param1' => 'value1'];

        $this->emailTemplateAttachmentProcessor
            ->expects(self::once())
            ->method('processAttachment')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn($processedAttachment);

        $this->fileManager
            ->expects(self::once())
            ->method('getFileContent')
            ->with('test.txt')
            ->willReturn('');

        $this->emailEntityBuilder
            ->expects(self::never())
            ->method('attachment');

        $result = $this->factory->createEmailAttachmentEntities($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testCreateEmailAttachmentEntityFromFileItemsWithNullFile(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setFilePlaceholder('entity.fileCollection');

        $processedAttachment = new EmailTemplateAttachmentModel();

        $fileItem1 = new FileItem();
        $fileItem1->setFile(null);

        $file2 = new File();
        $file2->setFilename('file2.txt');
        $file2->setOriginalFilename('file2.txt');
        $file2->setMimeType('text/plain');

        $fileItem2 = new FileItem();
        $fileItem2->setFile($file2);

        $processedAttachment->addFileItem($fileItem1);
        $processedAttachment->addFileItem($fileItem2);

        $emailAttachment2 = new EmailAttachmentEntity();
        $emailAttachmentContent2 = new EmailAttachmentContent();
        $templateParams = ['param1' => 'value1'];
        $fileContent = 'file content';

        $this->emailTemplateAttachmentProcessor
            ->expects(self::once())
            ->method('processAttachment')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn($processedAttachment);

        $this->fileManager
            ->expects(self::once())
            ->method('getFileContent')
            ->with('file2.txt')
            ->willReturn($fileContent);

        $this->emailEntityBuilder
            ->expects(self::once())
            ->method('attachment')
            ->with('file2.txt', 'text/plain')
            ->willReturn($emailAttachment2);

        $this->emailEntityBuilder
            ->expects(self::once())
            ->method('attachmentContent')
            ->with(base64_encode($fileContent), 'base64')
            ->willReturn($emailAttachmentContent2);

        $result = $this->factory->createEmailAttachmentEntities($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertSame($emailAttachment2, $result[1]);
    }

    public function testCreateEmailAttachmentEntityWhenFileItemContentNotFound(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setFilePlaceholder('entity.fileCollection');

        $processedAttachment = new EmailTemplateAttachmentModel();

        $file1 = new File();
        $file1->setFilename('file1.txt');
        $file1->setOriginalFilename('file1.txt');
        $file1->setMimeType('text/plain');

        $fileItem1 = new FileItem();
        $fileItem1->setFile($file1);

        $processedAttachment->addFileItem($fileItem1);

        $templateParams = ['param1' => 'value1'];
        $exception = new FileNotFound('File not found');

        $this->emailTemplateAttachmentProcessor
            ->expects(self::once())
            ->method('processAttachment')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn($processedAttachment);

        $this->fileManager
            ->expects(self::once())
            ->method('getFileContent')
            ->with('file1.txt')
            ->willThrowException($exception);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Failed to get content of the file "{filename}" of the email template attachment: {message}.',
                [
                    'filename' => 'file1.txt',
                    'message' => $exception->getMessage(),
                    'file' => $file1,
                    'exception' => $exception,
                ]
            );

        $this->emailEntityBuilder
            ->expects(self::never())
            ->method('attachment');

        $this->emailEntityBuilder
            ->expects(self::never())
            ->method('attachmentContent');

        $result = $this->factory->createEmailAttachmentEntities($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testCreateEmailAttachmentEntityWhenFileItemContentIsEmpty(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setFilePlaceholder('entity.fileCollection');

        $processedAttachment = new EmailTemplateAttachmentModel();

        $file1 = new File();
        $file1->setFilename('file1.txt');
        $file1->setOriginalFilename('file1.txt');
        $file1->setMimeType('text/plain');

        $file2 = new File();
        $file2->setFilename('file2.txt');
        $file2->setOriginalFilename('file2.txt');
        $file2->setMimeType('text/plain');

        $fileItem1 = new FileItem();
        $fileItem1->setFile($file1);
        $fileItem2 = new FileItem();
        $fileItem2->setFile($file2);

        $processedAttachment->addFileItem($fileItem1);
        $processedAttachment->addFileItem($fileItem2);

        $emailAttachment2 = new EmailAttachmentEntity();
        $emailAttachmentContent2 = new EmailAttachmentContent();
        $templateParams = ['param1' => 'value1'];
        $fileContent = 'file content';

        $this->emailTemplateAttachmentProcessor
            ->expects(self::once())
            ->method('processAttachment')
            ->with($emailTemplateAttachment, $templateParams)
            ->willReturn($processedAttachment);

        $this->fileManager
            ->expects(self::exactly(2))
            ->method('getFileContent')
            ->willReturnMap([
                ['file1.txt', true, ''],
                ['file2.txt', true, $fileContent],
            ]);

        $this->emailEntityBuilder
            ->expects(self::once())
            ->method('attachment')
            ->with('file2.txt', 'text/plain')
            ->willReturn($emailAttachment2);

        $this->emailEntityBuilder
            ->expects(self::once())
            ->method('attachmentContent')
            ->with(base64_encode($fileContent), 'base64')
            ->willReturn($emailAttachmentContent2);

        $result = $this->factory->createEmailAttachmentEntities($emailTemplateAttachment, $templateParams);

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertSame($emailAttachment2, $result[1]);
    }
}
