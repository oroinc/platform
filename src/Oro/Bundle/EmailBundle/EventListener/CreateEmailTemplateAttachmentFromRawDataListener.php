<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Oro\Bundle\EmailBundle\Event\EmailTemplateFromArrayHydrateBeforeEvent;
use Symfony\Component\Mime\MimeTypesInterface;

/**
 * Listens for the {@see EmailTemplateFromArrayHydrateBeforeEvent} event to replace raw email template attachments
 * in the email template data with corresponding email template attachment entities.
 */
class CreateEmailTemplateAttachmentFromRawDataListener
{
    /**
     * @param FileManager $fileManager
     * @param MimeTypesInterface $mimeTypes
     * @param string $applicableEmailTemplateClass Email template class for which this listener is applicable,
     *  e.g. {@see EmailTemplate}
     * @param string $emailTemplateAttachmentClass Email template attachment class to be created, e.g.
     *  {@see EmailTemplateAttachment}
     */
    public function __construct(
        private readonly FileManager $fileManager,
        private readonly MimeTypesInterface $mimeTypes,
        private readonly string $applicableEmailTemplateClass,
        private readonly string $emailTemplateAttachmentClass
    ) {
    }

    public function onEmailTemplateFromRawDataHydrateBeforeEvent(EmailTemplateFromArrayHydrateBeforeEvent $event): void
    {
        if (!$event->getEmailTemplate() instanceof $this->applicableEmailTemplateClass) {
            return;
        }

        $data = $event->getData();
        if (empty($data['attachments']) || !is_array($data['attachments'])) {
            return;
        }

        foreach ($data['attachments'] as $key => $rawAttachment) {
            if (!is_string($rawAttachment)) {
                continue;
            }

            $data['attachments'][$key] = new $this->emailTemplateAttachmentClass();

            if (str_starts_with($rawAttachment, '{{')) {
                $data['attachments'][$key]->setFilePlaceholder(trim($rawAttachment, '{ }'));
            } else {
                $fileEntity = $this->createFileEntity((string)$key, $rawAttachment);

                $data['attachments'][$key]->setFile($fileEntity);
            }
        }

        $event->setData($data);
    }

    private function createFileEntity(string $name, string $base64Content): File
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);

        $tempFile = $this->fileManager->writeToTemporaryFile(base64_decode($base64Content));

        $file = new File();
        $file->setFile($tempFile);
        $file->setOriginalFilename($name);
        $file->setMimeType($this->mimeTypes->getMimeTypes($extension)[0] ?? 'application/octet-stream');

        return $file;
    }
}
