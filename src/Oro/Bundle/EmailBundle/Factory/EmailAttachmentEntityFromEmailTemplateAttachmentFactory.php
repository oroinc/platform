<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Factory;

use Gaufrette\Exception\FileNotFound;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment as EmailAttachmentEntity;
use Oro\Bundle\EmailBundle\Model\EmailTemplateAttachmentModel;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateAttachmentProcessor;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Creates an {@see EmailAttachmentEntity} entity from an {@see EmailTemplateAttachmentModel}.
 */
class EmailAttachmentEntityFromEmailTemplateAttachmentFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly EmailEntityBuilder $emailEntityBuilder,
        private readonly EmailTemplateAttachmentProcessor $emailTemplateAttachmentProcessor,
        private readonly FileManager $fileManager
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * Creates an email attachment entity from an email template attachment.
     *
     * @param EmailTemplateAttachmentModel $emailTemplateAttachment
     * @param array<string,mixed> $templateParams
     *
     * @return array<EmailAttachmentEntity>
     */
    public function createEmailAttachmentEntities(
        EmailTemplateAttachmentModel $emailTemplateAttachment,
        array $templateParams
    ): array {
        $processedEmailTemplateAttachment = $this->emailTemplateAttachmentProcessor
            ->processAttachment($emailTemplateAttachment, $templateParams);

        if (!$processedEmailTemplateAttachment) {
            return [];
        }

        $emailAttachments = [];

        $file = $processedEmailTemplateAttachment->getFile();
        if ($file) {
            $emailAttachment = $this->createEmailAttachmentFromFile($file);
            if ($emailAttachment) {
                $emailAttachments[0] = $emailAttachment;
            }
        } elseif ($processedEmailTemplateAttachment->getFileItems()?->count()) {
            foreach ($processedEmailTemplateAttachment->getFileItems() as $index => $fileItem) {
                $fileItemFile = $fileItem->getFile();
                if (!$fileItemFile) {
                    continue;
                }

                $emailAttachment = $this->createEmailAttachmentFromFile($fileItemFile);
                if ($emailAttachment) {
                    $emailAttachments[$index] = $emailAttachment;
                }
            }
        }

        return $emailAttachments;
    }

    private function createEmailAttachmentFromFile(File $file): ?EmailAttachmentEntity
    {
        $fileContent = $this->getFileContent($file);
        if (!$fileContent) {
            return null;
        }

        $emailAttachment = $this->emailEntityBuilder
            ->attachment($file->getOriginalFilename(), $file->getMimeType());

        $emailAttachmentContent = $this->emailEntityBuilder
            ->attachmentContent(base64_encode($fileContent), 'base64');

        $emailAttachment->setContent($emailAttachmentContent);

        return $emailAttachment;
    }

    private function getFileContent(File $file): ?string
    {
        try {
            $content = $this->fileManager->getFileContent($file->getFilename());
        } catch (FileNotFound $exception) {
            $content = null;
            $this->logger->error(
                'Failed to get content of the file "{filename}" of the email template attachment: {message}.',
                [
                    'filename' => $file->getFilename(),
                    'message' => $exception->getMessage(),
                    'file' => $file,
                    'exception' => $exception,
                ]
            );
        }

        return $content;
    }
}
