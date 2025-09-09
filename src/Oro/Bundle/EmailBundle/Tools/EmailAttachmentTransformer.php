<?php

namespace Oro\Bundle\EmailBundle\Tools;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Factory\EmailAttachmentEntityFromEmailTemplateAttachmentFactory;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EmailBundle\Form\Model\Factory as EmailModelFactory;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateAttachmentModel;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Provides methods to do the following transformation:
 * * EmailAttachment entity to EmailAttachment model
 * * Attachment entity to EmailAttachment model
 * * Attachment entity to EmailAttachment entity
 * * UploadedFile object to EmailAttachment entity
 */
class EmailAttachmentTransformer
{
    private EmailModelFactory $factory;

    private FileManager $fileManager;

    private AttachmentManager $attachmentManager;

    private EmailAttachmentManager $emailAttachmentManager;

    private EmailAttachmentEntityFromEmailTemplateAttachmentFactory $emailAttachmentEntityFactory;

    public function __construct(
        EmailModelFactory $factory,
        FileManager $fileManager,
        AttachmentManager $manager,
        EmailAttachmentManager $emailAttachmentManager,
        EmailAttachmentEntityFromEmailTemplateAttachmentFactory $emailAttachmentEntityFactory
    ) {
        $this->factory = $factory;
        $this->fileManager = $fileManager;
        $this->attachmentManager = $manager;
        $this->emailAttachmentManager = $emailAttachmentManager;
        $this->emailAttachmentEntityFactory = $emailAttachmentEntityFactory;
    }

    public function entityToModel(EmailAttachment $emailAttachment): EmailAttachmentModel
    {
        $attachmentModel = $this->factory->getEmailAttachment();

        $mimeType = $emailAttachment->getContentType();

        $attachmentModel->setEmailAttachment($emailAttachment);
        $attachmentModel->setType(EmailAttachmentModel::TYPE_EMAIL_ATTACHMENT);
        $attachmentModel->setId($emailAttachment->getId());
        $attachmentModel->setFileSize($emailAttachment->getSize());
        $attachmentModel->setMimeType($mimeType);
        $attachmentModel->setModified($emailAttachment->getEmailBody()->getCreated());
        $attachmentModel->setIcon($this->attachmentManager->getAttachmentIconClass($emailAttachment));
        if ($this->attachmentManager->isImageType($mimeType)) {
            $attachmentModel->setPreview(
                $this->emailAttachmentManager->getResizedImageUrl(
                    $emailAttachment,
                    AttachmentManager::THUMBNAIL_WIDTH,
                    AttachmentManager::THUMBNAIL_HEIGHT
                )
            );
        }

        return $attachmentModel;
    }

    public function attachmentEntityToModel(Attachment $attachment): EmailAttachmentModel
    {
        $attachmentModel = $this->factory->getEmailAttachment();

        $mimeType = $attachment->getFile()->getMimeType();

        $attachmentModel->setType(EmailAttachmentModel::TYPE_ATTACHMENT);
        $attachmentModel->setId($attachment->getId());
        $attachmentModel->setFileName($attachment->getFile()->getOriginalFilename());
        $attachmentModel->setFileSize($attachment->getFile()->getFileSize());
        $attachmentModel->setMimeType($mimeType);
        $attachmentModel->setModified($attachment->getCreatedAt());
        $attachmentModel->setIcon($this->attachmentManager->getAttachmentIconClass($attachment->getFile()));
        if ($this->attachmentManager->isImageType($mimeType)) {
            $attachmentModel->setPreview(
                $this->attachmentManager->getResizedImageUrl(
                    $attachment->getFile(),
                    AttachmentManager::THUMBNAIL_WIDTH,
                    AttachmentManager::THUMBNAIL_HEIGHT
                )
            );
        }

        return $attachmentModel;
    }

    public function attachmentEntityToEntity(Attachment $attachment): EmailAttachment
    {
        $emailAttachment = new EmailAttachment();

        $emailAttachment->setFileName($attachment->getFile()->getFilename());

        $emailAttachmentContent = new EmailAttachmentContent();
        $emailAttachmentContent->setContent(
            base64_encode($this->fileManager->getContent($attachment->getFile()))
        );

        $emailAttachmentContent->setContentTransferEncoding('base64');
        $emailAttachmentContent->setEmailAttachment($emailAttachment);

        $emailAttachment->setContent($emailAttachmentContent);
        $emailAttachment->setContentType($attachment->getFile()->getMimeType());
        $emailAttachment->setFile($attachment->getFile());
        $emailAttachment->setFileName($attachment->getFile()->getOriginalFilename());

        return $emailAttachment;
    }

    public function entityFromUploadedFile(UploadedFile $uploadedFile): EmailAttachment
    {
        $emailAttachment = new EmailAttachment();

        $attachmentContent = new EmailAttachmentContent();
        $attachmentContent->setContent(
            base64_encode(file_get_contents($uploadedFile->getRealPath()))
        );
        $attachmentContent->setContentTransferEncoding('base64');
        $attachmentContent->setEmailAttachment($emailAttachment);

        $emailAttachment->setContent($attachmentContent);
        $emailAttachment->setContentType($uploadedFile->getMimeType());
        $emailAttachment->setFileName($uploadedFile->getClientOriginalName());

        return $emailAttachment;
    }

    /**
     * Creates an EmailAttachment entity from an EmailTemplateAttachmentModel.
     *
     * @param EmailTemplateAttachmentModel $emailTemplateAttachment
     * @param array<string,mixed> $templateParams
     *
     * @return array<EmailAttachment>
     */
    public function entityFromEmailTemplateAttachment(
        EmailTemplateAttachmentModel $emailTemplateAttachment,
        array $templateParams = []
    ): array {
        return $this->emailAttachmentEntityFactory
            ->createEmailAttachmentEntities($emailTemplateAttachment, $templateParams);
    }
}
