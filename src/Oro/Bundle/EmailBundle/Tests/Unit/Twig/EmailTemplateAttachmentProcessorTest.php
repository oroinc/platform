<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\EmailBundle\Model\EmailTemplateAttachmentModel;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateAttachmentProcessor;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateData;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateDataFactory;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EmailTemplateAttachmentProcessorTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private EmailTemplateAttachmentProcessor $processor;
    private MockObject&TemplateDataFactory $templateDataFactory;

    protected function setUp(): void
    {
        $this->templateDataFactory = $this->createMock(TemplateDataFactory::class);

        $this->processor = new EmailTemplateAttachmentProcessor(
            $this->templateDataFactory
        );

        $this->setUpLoggerMock($this->processor);
    }

    public function testProcessAttachmentWithExistingFile(): void
    {
        $file = new File();
        $file->setFilename('test-filename');
        $file->setOriginalFilename('document.pdf');
        $file->setMimeType('application/pdf');

        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(123);
        $emailTemplateAttachment->setFile($file);

        $templateParams = ['param' => 'value'];

        $processedAttachment = $this->processor->processAttachment($emailTemplateAttachment, $templateParams);

        self::assertNotSame($emailTemplateAttachment, $processedAttachment);
        self::assertEquals(123, $processedAttachment->getId());
        self::assertSame($file, $processedAttachment->getFile());
        self::assertNull($processedAttachment->getFilePlaceholder());
    }

    public function testProcessAttachmentWithFilePlaceholder(): void
    {
        $file = new File();
        $file->setFilename('test-filename');
        $file->setOriginalFilename('document.pdf');
        $file->setMimeType('application/pdf');

        $templateData = $this->createMock(TemplateData::class);
        $templateData->expects(self::once())
            ->method('getEntityVariable')
            ->with('entity.file')
            ->willReturn($file);

        $templateParams = ['param' => 'value'];

        $this->templateDataFactory->expects(self::once())
            ->method('createTemplateData')
            ->with($templateParams)
            ->willReturn($templateData);

        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(456);
        $emailTemplateAttachment->setFilePlaceholder('entity.file');

        $processedAttachment = $this->processor->processAttachment($emailTemplateAttachment, $templateParams);

        self::assertNotSame($emailTemplateAttachment, $processedAttachment);
        self::assertEquals(456, $processedAttachment->getId());
        self::assertSame($file, $processedAttachment->getFile());
        self::assertEquals('entity.file', $processedAttachment->getFilePlaceholder());
    }

    public function testProcessAttachmentWithFilePlaceholderReturnsNull(): void
    {
        $templateData = $this->createMock(TemplateData::class);
        $templateData->expects(self::once())
            ->method('getEntityVariable')
            ->with('entity.file')
            ->willReturn(null);

        $templateParams = ['param' => 'value'];

        $this->templateDataFactory->expects(self::once())
            ->method('createTemplateData')
            ->with($templateParams)
            ->willReturn($templateData);

        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(789);
        $emailTemplateAttachment->setFilePlaceholder('entity.file');

        $processedAttachment = $this->processor->processAttachment($emailTemplateAttachment, $templateParams);

        self::assertNotSame($emailTemplateAttachment, $processedAttachment);
        self::assertEquals(789, $processedAttachment->getId());
        self::assertNull($processedAttachment->getFile());
        self::assertEquals('entity.file', $processedAttachment->getFilePlaceholder());
    }

    public function testProcessAttachmentWithFileItemCollection(): void
    {
        $file1 = new File();
        $file1->setFilename('file1.txt');
        $file1->setOriginalFilename('document1.txt');
        $file1->setMimeType('text/plain');

        $file2 = new File();
        $file2->setFilename('file2.pdf');
        $file2->setOriginalFilename('document2.pdf');
        $file2->setMimeType('application/pdf');

        $fileItem1 = new FileItem();
        $fileItem1->setFile($file1);
        $fileItem2 = new FileItem();
        $fileItem2->setFile($file2);

        $fileItemCollection = new ArrayCollection([$fileItem1, $fileItem2]);

        $templateData = $this->createMock(TemplateData::class);
        $templateData->expects(self::once())
            ->method('getEntityVariable')
            ->with('entity.fileCollection')
            ->willReturn($fileItemCollection);

        $templateParams = ['param' => 'value'];

        $this->templateDataFactory->expects(self::once())
            ->method('createTemplateData')
            ->with($templateParams)
            ->willReturn($templateData);

        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(123);
        $emailTemplateAttachment->setFilePlaceholder('entity.fileCollection');

        $processedAttachment = $this->processor->processAttachment($emailTemplateAttachment, $templateParams);

        self::assertNotSame($emailTemplateAttachment, $processedAttachment);
        self::assertEquals(123, $processedAttachment->getId());
        self::assertNull($processedAttachment->getFile());
        self::assertEquals('entity.fileCollection', $processedAttachment->getFilePlaceholder());

        $processedFileItems = $processedAttachment->getFileItems();
        self::assertCount(2, $processedFileItems);
        self::assertTrue($processedFileItems->contains($fileItem1));
        self::assertTrue($processedFileItems->contains($fileItem2));
    }

    public function testProcessAttachmentWithEmptyFileItemCollection(): void
    {
        $fileItemCollection = new ArrayCollection([]);

        $templateData = $this->createMock(TemplateData::class);
        $templateData->expects(self::once())
            ->method('getEntityVariable')
            ->with('entity.fileCollection')
            ->willReturn($fileItemCollection);

        $templateParams = ['param' => 'value'];

        $this->templateDataFactory->expects(self::once())
            ->method('createTemplateData')
            ->with($templateParams)
            ->willReturn($templateData);

        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(456);
        $emailTemplateAttachment->setFilePlaceholder('entity.fileCollection');

        $processedAttachment = $this->processor->processAttachment($emailTemplateAttachment, $templateParams);

        self::assertNotSame($emailTemplateAttachment, $processedAttachment);
        self::assertEquals(456, $processedAttachment->getId());
        self::assertNull($processedAttachment->getFile());
        self::assertEquals('entity.fileCollection', $processedAttachment->getFilePlaceholder());
        self::assertCount(0, $processedAttachment->getFileItems());
    }

    public function testProcessAttachmentWithInvalidFileItemInCollection(): void
    {
        $file = new File();
        $file->setFilename('valid-file.txt');

        $validFileItem = new FileItem();
        $validFileItem->setFile($file);

        $invalidItem = new \stdClass(); // Invalid item in collection

        $fileItemCollection = new ArrayCollection([$validFileItem, $invalidItem]);

        $templateData = $this->createMock(TemplateData::class);
        $templateData->expects(self::once())
            ->method('getEntityVariable')
            ->with('entity.fileCollection')
            ->willReturn($fileItemCollection);

        $templateParams = ['param' => 'value'];

        $this->templateDataFactory->expects(self::once())
            ->method('createTemplateData')
            ->with($templateParams)
            ->willReturn($templateData);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'The file placeholder "{file_placeholder}" is expected to be computed into '
                . 'a {file} entity or collection of {file_item} entities, '
                . 'but got "{file_type}" at {index}.',
                [
                    'file_placeholder' => 'entity.fileCollection',
                    'file' => File::class,
                    'file_item' => FileItem::class,
                    'file_type' => 'stdClass',
                    'index' => 1,
                ]
            );

        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(789);
        $emailTemplateAttachment->setFilePlaceholder('entity.fileCollection');

        $processedAttachment = $this->processor->processAttachment($emailTemplateAttachment, $templateParams);

        self::assertNotSame($emailTemplateAttachment, $processedAttachment);
        self::assertEquals(789, $processedAttachment->getId());
        self::assertNull($processedAttachment->getFile());
        self::assertEquals('entity.fileCollection', $processedAttachment->getFilePlaceholder());

        // Should still contain the valid FileItem despite the invalid one
        $processedFileItems = $processedAttachment->getFileItems();
        self::assertCount(1, $processedFileItems);
        self::assertTrue($processedFileItems->contains($validFileItem));
    }

    public function testProcessAttachmentWithNonFileComputedVariable(): void
    {
        $templateData = $this->createMock(TemplateData::class);
        $templateData->expects(self::once())
            ->method('getEntityVariable')
            ->with('entity.file')
            ->willReturn(new \stdClass());

        $templateParams = ['param' => 'value'];

        $this->templateDataFactory->expects(self::once())
            ->method('createTemplateData')
            ->with($templateParams)
            ->willReturn($templateData);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'The file placeholder "{file_placeholder}" is expected to be computed into '
                . 'a {file} entity or collection of {file_item} entities, but got "{file_type}".',
                [
                    'file_placeholder' => 'entity.file',
                    'file' => File::class,
                    'file_item' => FileItem::class,
                    'file_type' => 'stdClass',
                ]
            );

        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(101);
        $emailTemplateAttachment->setFilePlaceholder('entity.file');

        $processedAttachment = $this->processor->processAttachment($emailTemplateAttachment, $templateParams);

        self::assertNotSame($emailTemplateAttachment, $processedAttachment);
        self::assertEquals(101, $processedAttachment->getId());
        self::assertNull($processedAttachment->getFile());
        self::assertEquals('entity.file', $processedAttachment->getFilePlaceholder());
    }

    public function testProcessAttachmentWithNoFileAndNoPlaceholder(): void
    {
        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(202);

        $templateParams = ['param' => 'value'];

        // TemplateDataFactory should not be called since there's no placeholder
        $this->templateDataFactory->expects(self::never())
            ->method('createTemplateData');

        $processedAttachment = $this->processor->processAttachment($emailTemplateAttachment, $templateParams);

        self::assertNotSame($emailTemplateAttachment, $processedAttachment);
        self::assertEquals(202, $processedAttachment->getId());
        self::assertNull($processedAttachment->getFile());
        self::assertNull($processedAttachment->getFilePlaceholder());
    }

    public function testProcessAttachmentWithEmptyTemplateParams(): void
    {
        $file = new File();
        $file->setFilename('test.txt');

        $templateData = $this->createMock(TemplateData::class);
        $templateData->expects(self::once())
            ->method('getEntityVariable')
            ->with('entity.file')
            ->willReturn($file);

        $this->templateDataFactory->expects(self::once())
            ->method('createTemplateData')
            ->with([])
            ->willReturn($templateData);

        $emailTemplateAttachment = new EmailTemplateAttachmentModel();
        $emailTemplateAttachment->setId(303);
        $emailTemplateAttachment->setFilePlaceholder('entity.file');

        $processedAttachment = $this->processor->processAttachment($emailTemplateAttachment);

        self::assertNotSame($emailTemplateAttachment, $processedAttachment);
        self::assertEquals(303, $processedAttachment->getId());
        self::assertSame($file, $processedAttachment->getFile());
        self::assertEquals('entity.file', $processedAttachment->getFilePlaceholder());
    }
}
