<?php

namespace Oro\Bundle\EmailBundle\Tools;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EmailBundle\Form\Model\Factory;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;
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
    /** @var Factory */
    private $factory;

    /** @var FileManager */
    private $fileManager;

    /** @var AttachmentManager */
    private $manager;

    /** @var EmailAttachmentManager */
    private $emailAttachmentManager;

    public function __construct(
        Factory $factory,
        FileManager $fileManager,
        AttachmentManager $manager,
        EmailAttachmentManager $emailAttachmentManager
    ) {
        $this->factory = $factory;
        $this->fileManager = $fileManager;
        $this->manager = $manager;
        $this->emailAttachmentManager = $emailAttachmentManager;
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
        $attachmentModel->setIcon($this->manager->getAttachmentIconClass($emailAttachment));
        if ($this->manager->isImageType($mimeType)) {
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
        $attachmentModel->setIcon($this->manager->getAttachmentIconClass($attachment->getFile()));
        if ($this->manager->isImageType($mimeType)) {
            $attachmentModel->setPreview(
                $this->manager->getResizedImageUrl(
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
}
