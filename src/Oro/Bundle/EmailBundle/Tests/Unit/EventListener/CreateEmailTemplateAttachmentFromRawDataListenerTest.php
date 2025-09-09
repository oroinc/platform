<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Oro\Bundle\EmailBundle\Event\EmailTemplateFromArrayHydrateBeforeEvent;
use Oro\Bundle\EmailBundle\EventListener\CreateEmailTemplateAttachmentFromRawDataListener;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\Mime\MimeTypesInterface;

final class CreateEmailTemplateAttachmentFromRawDataListenerTest extends TestCase
{
    private CreateEmailTemplateAttachmentFromRawDataListener $listener;

    private MockObject&FileManager $fileManager;

    private MockObject&MimeTypesInterface $mimeTypes;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->mimeTypes = $this->createMock(MimeTypesInterface::class);
        $this->listener = new CreateEmailTemplateAttachmentFromRawDataListener(
            $this->fileManager,
            $this->mimeTypes,
            EmailTemplate::class,
            EmailTemplateAttachment::class
        );
    }

    public function testOnEmailTemplateFromRawDataHydrateBeforeEventWithNonApplicableEmailTemplate(): void
    {
        $event = $this->createMock(EmailTemplateFromArrayHydrateBeforeEvent::class);
        $event
            ->expects(self::once())
            ->method('getEmailTemplate')
            ->willReturn(new EmailTemplateModel());
        $event
            ->expects(self::never())
            ->method('getData');
        $event
            ->expects(self::never())
            ->method('setData');

        $this->listener->onEmailTemplateFromRawDataHydrateBeforeEvent($event);
    }

    public function testOnEmailTemplateFromRawDataHydrateBeforeEventWithNoAttachments(): void
    {
        $emailTemplate = $this->createMock(EmailTemplate::class);
        $data = ['someField' => 'value'];

        $event = $this->createMock(EmailTemplateFromArrayHydrateBeforeEvent::class);
        $event
            ->expects(self::once())
            ->method('getEmailTemplate')
            ->willReturn($emailTemplate);
        $event
            ->expects(self::once())
            ->method('getData')
            ->willReturn($data);
        $event
            ->expects(self::never())
            ->method('setData');

        $this->listener->onEmailTemplateFromRawDataHydrateBeforeEvent($event);
    }

    public function testOnEmailTemplateFromRawDataHydrateBeforeEventWithEmptyAttachments(): void
    {
        $emailTemplate = $this->createMock(EmailTemplate::class);
        $data = ['attachments' => []];

        $event = $this->createMock(EmailTemplateFromArrayHydrateBeforeEvent::class);
        $event
            ->expects(self::once())
            ->method('getEmailTemplate')
            ->willReturn($emailTemplate);
        $event
            ->expects(self::once())
            ->method('getData')
            ->willReturn($data);
        $event
            ->expects(self::never())
            ->method('setData');

        $this->listener->onEmailTemplateFromRawDataHydrateBeforeEvent($event);
    }

    public function testOnEmailTemplateFromRawDataHydrateBeforeEventWithNonStringAttachment(): void
    {
        $emailTemplate = $this->createMock(EmailTemplate::class);
        $data = ['attachments' => ['file' => ['not a string']]];

        $event = $this->createMock(EmailTemplateFromArrayHydrateBeforeEvent::class);
        $event
            ->expects(self::once())
            ->method('getEmailTemplate')
            ->willReturn($emailTemplate);
        $event
            ->expects(self::once())
            ->method('getData')
            ->willReturn($data);
        $event
            ->expects(self::once())
            ->method('setData')
            ->with($data);

        $this->listener->onEmailTemplateFromRawDataHydrateBeforeEvent($event);
    }

    public function testOnEmailTemplateFromRawDataHydrateBeforeEventWithFilePlaceholder(): void
    {
        $emailTemplate = $this->createMock(EmailTemplate::class);
        $placeholder = '{{ file.placeholder }}';
        $data = ['attachments' => ['file' => $placeholder]];

        $event = $this->createMock(EmailTemplateFromArrayHydrateBeforeEvent::class);
        $event
            ->expects(self::once())
            ->method('getEmailTemplate')
            ->willReturn($emailTemplate);
        $event
            ->expects(self::once())
            ->method('getData')
            ->willReturn($data);
        $event
            ->expects(self::once())
            ->method('setData')
            ->with(
                self::callback(static function ($updatedData) {
                    $attachment = $updatedData['attachments']['file'];
                    return $attachment instanceof EmailTemplateAttachment
                        && $attachment->getFilePlaceholder() === 'file.placeholder'
                        && $attachment->getFile() === null;
                })
            );

        $this->listener->onEmailTemplateFromRawDataHydrateBeforeEvent($event);
    }

    public function testOnEmailTemplateFromRawDataHydrateBeforeEventWithFileContent(): void
    {
        $emailTemplate = $this->createMock(EmailTemplate::class);
        $base64Content = base64_encode('file content');
        $data = ['attachments' => ['document.pdf' => $base64Content]];

        $tempFile = $this->createMock(ComponentFile::class);
        $this->fileManager
            ->expects(self::once())
            ->method('writeToTemporaryFile')
            ->with('file content')
            ->willReturn($tempFile);

        $this->mimeTypes
            ->expects(self::once())
            ->method('getMimeTypes')
            ->with('pdf')
            ->willReturn(['application/pdf']);

        $event = $this->createMock(EmailTemplateFromArrayHydrateBeforeEvent::class);
        $event
            ->expects(self::once())
            ->method('getEmailTemplate')
            ->willReturn($emailTemplate);
        $event
            ->expects(self::once())
            ->method('getData')
            ->willReturn($data);
        $event
            ->expects(self::once())
            ->method('setData')
            ->with(
                self::callback(static function ($updatedData) use ($tempFile) {
                    $attachment = $updatedData['attachments']['document.pdf'];
                    if (!$attachment instanceof EmailTemplateAttachment) {
                        return false;
                    }

                    $file = $attachment->getFile();
                    return $file instanceof File
                        && $file->getFile() === $tempFile
                        && $file->getOriginalFilename() === 'document.pdf'
                        && $file->getMimeType() === 'application/pdf'
                        && $attachment->getFilePlaceholder() === null;
                })
            );

        $this->listener->onEmailTemplateFromRawDataHydrateBeforeEvent($event);
    }

    public function testOnEmailTemplateFromRawDataHydrateBeforeEventWithUnknownExtension(): void
    {
        $emailTemplate = $this->createMock(EmailTemplate::class);
        $base64Content = base64_encode('file content');
        $data = ['attachments' => ['document.unknown' => $base64Content]];

        $tempFile = $this->createMock(ComponentFile::class);
        $this->fileManager
            ->expects(self::once())
            ->method('writeToTemporaryFile')
            ->with('file content')
            ->willReturn($tempFile);

        $this->mimeTypes
            ->expects(self::once())
            ->method('getMimeTypes')
            ->with('unknown')
            ->willReturn([]);

        $event = $this->createMock(EmailTemplateFromArrayHydrateBeforeEvent::class);
        $event
            ->expects(self::once())
            ->method('getEmailTemplate')
            ->willReturn($emailTemplate);
        $event
            ->expects(self::once())
            ->method('getData')
            ->willReturn($data);
        $event
            ->expects(self::once())
            ->method('setData')
            ->with(
                self::callback(static function ($updatedData) use ($tempFile) {
                    $attachment = $updatedData['attachments']['document.unknown'];
                    if (!$attachment instanceof EmailTemplateAttachment) {
                        return false;
                    }

                    $file = $attachment->getFile();
                    return $file instanceof File
                        && $file->getFile() === $tempFile
                        && $file->getOriginalFilename() === 'document.unknown'
                        && $file->getMimeType() === 'application/octet-stream'
                        && $attachment->getFilePlaceholder() === null;
                })
            );

        $this->listener->onEmailTemplateFromRawDataHydrateBeforeEvent($event);
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    public function testOnEmailTemplateFromRawDataHydrateBeforeEventWithMultipleAttachments(): void
    {
        $emailTemplate = $this->createMock(EmailTemplate::class);
        $placeholder = '{{file.placeholder}}';
        $base64Content = base64_encode('file content');
        $data = [
            'attachments' => [
                'placeholder' => $placeholder,
                'document.pdf' => $base64Content,
                'not-a-string' => ['array'],
            ],
        ];

        $tempFile = $this->createMock(ComponentFile::class);
        $this->fileManager
            ->expects(self::once())
            ->method('writeToTemporaryFile')
            ->with('file content')
            ->willReturn($tempFile);

        $this->mimeTypes
            ->expects(self::once())
            ->method('getMimeTypes')
            ->with('pdf')
            ->willReturn(['application/pdf']);

        $event = $this->createMock(EmailTemplateFromArrayHydrateBeforeEvent::class);
        $event
            ->expects(self::once())
            ->method('getEmailTemplate')
            ->willReturn($emailTemplate);
        $event
            ->expects(self::once())
            ->method('getData')
            ->willReturn($data);
        $event
            ->expects(self::once())
            ->method('setData')
            ->with(
                self::callback(static function ($updatedData) use ($tempFile) {
                    $placeholderAttachment = $updatedData['attachments']['placeholder'];
                    $fileAttachment = $updatedData['attachments']['document.pdf'];
                    $nonStringAttachment = $updatedData['attachments']['not-a-string'];

                    if (!$placeholderAttachment instanceof EmailTemplateAttachment) {
                        return false;
                    }
                    if (!$fileAttachment instanceof EmailTemplateAttachment) {
                        return false;
                    }

                    $file = $fileAttachment->getFile();

                    return $placeholderAttachment->getFilePlaceholder() === 'file.placeholder'
                        && $placeholderAttachment->getFile() === null
                        && $file instanceof File
                        && $file->getFile() === $tempFile
                        && $file->getOriginalFilename() === 'document.pdf'
                        && $file->getMimeType() === 'application/pdf'
                        && $fileAttachment->getFilePlaceholder() === null
                        && $nonStringAttachment === ['array'];
                })
            );

        $this->listener->onEmailTemplateFromRawDataHydrateBeforeEvent($event);
    }
}
