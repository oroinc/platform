<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Factory;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EmailBundle\Form\Model\Factory;
use Oro\Bundle\EmailBundle\Model\EmailTemplateAttachmentModel;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateAttachmentProcessor;

/**
 * Creates an email attachment model from an email template attachment.
 */
class EmailAttachmentModelFromEmailTemplateAttachmentFactory
{
    public function __construct(
        private readonly Factory $emailModelFactory,
        private readonly EmailTemplateAttachmentProcessor $emailTemplateAttachmentProcessor,
        private readonly AttachmentManager $attachmentManager
    ) {
    }

    /**
     * Creates an email attachment model from an email template attachment.
     *
     * @param EmailTemplateAttachmentModel $emailTemplateAttachment
     * @param array<string,mixed> $templateParams
     *
     * @return array<EmailAttachmentModel>
     */
    public function createEmailAttachmentModels(
        EmailTemplateAttachmentModel $emailTemplateAttachment,
        array $templateParams
    ): array {
        $emailAttachment = $this->emailModelFactory->getEmailAttachment();
        $emailAttachment->setId($emailTemplateAttachment->getId());
        $emailAttachment->setType(EmailAttachmentModel::TYPE_EMAIL_TEMPLATE_ATTACHMENT);
        $emailAttachment->setEmailTemplateAttachment($emailTemplateAttachment);

        $processedEmailTemplateAttachment = $this->emailTemplateAttachmentProcessor
            ->processAttachment($emailTemplateAttachment, $templateParams);

        if (!$processedEmailTemplateAttachment) {
            return [];
        }

        $emailAttachments = [];

        $file = $processedEmailTemplateAttachment->getFile();
        if ($file) {
            $this->handleEmailAttachmentFile($emailAttachment, $file);

            $emailAttachments[0] = $emailAttachment;
        } elseif ($processedEmailTemplateAttachment->getFileItems()?->count()) {
            foreach ($processedEmailTemplateAttachment->getFileItems() as $index => $fileItem) {
                $fileItemFile = $fileItem->getFile();
                if (!$fileItemFile) {
                    continue;
                }

                $emailAttachmentItem = clone $emailAttachment;
                $this->handleEmailAttachmentFile($emailAttachmentItem, $fileItemFile);

                $emailAttachments[$index] = $emailAttachmentItem;
            }
        }

        return $emailAttachments;
    }

    private function handleEmailAttachmentFile(
        EmailAttachmentModel $emailAttachment,
        File $emailTemplateAttachmentFile
    ): void {
        $emailAttachment->setFileName($emailTemplateAttachmentFile->getOriginalFilename());
        $emailAttachment->setMimeType($emailTemplateAttachmentFile->getMimeType());
        $emailAttachment->setFileSize($emailTemplateAttachmentFile->getFileSize());
        $emailAttachment->setModified($emailTemplateAttachmentFile->getCreatedAt());
        $emailAttachment->setIcon($this->attachmentManager->getAttachmentIconClass($emailTemplateAttachmentFile));
        if ($this->attachmentManager->isImageType($emailTemplateAttachmentFile->getMimeType())) {
            $emailAttachment->setPreview(
                $this->attachmentManager->getResizedImageUrl(
                    $emailTemplateAttachmentFile,
                    AttachmentManager::THUMBNAIL_WIDTH,
                    AttachmentManager::THUMBNAIL_HEIGHT
                )
            );
        }
    }
}
