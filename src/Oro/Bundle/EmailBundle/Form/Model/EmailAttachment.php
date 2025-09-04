<?php

namespace Oro\Bundle\EmailBundle\Form\Model;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment as EmailAttachmentEntity;
use Oro\Bundle\EmailBundle\Model\EmailTemplateAttachmentModel;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Model for Email Attachment
 */
class EmailAttachment
{
    public const int TYPE_ATTACHMENT = 1; // oro attachment (OroAttachmentBundle)
    public const int TYPE_EMAIL_ATTACHMENT = 2; // email attachment
    public const int TYPE_UPLOADED = 3; // new uploaded file
    public const int TYPE_EMAIL_TEMPLATE_ATTACHMENT = 4; // email template attachment

    /**
     * @var int|string
     */
    protected $id;

    /**
     * @var int
     */
    protected $type;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var int
     */
    protected $fileSize;

    /**
     * @var string
     */
    protected $modified;

    /**
     * @var EmailAttachmentEntity
     */
    protected $emailAttachment;

    protected ?EmailTemplateAttachmentModel $emailTemplateAttachment = null;

    /**
     * @var UploadedFile
     */
    protected $file;

    /**
     * @var string
     */
    protected $preview;

    /**
     * @var string
     */
    protected $icon;

    /**
     * @var string
     */
    protected $mimeType;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return EmailAttachmentEntity
     */
    public function getEmailAttachment()
    {
        return $this->emailAttachment;
    }

    /**
     * @param EmailAttachmentEntity $emailAttachment
     *
     * @return $this
     */
    public function setEmailAttachment($emailAttachment)
    {
        $this->emailAttachment = $emailAttachment;
        if ($this->emailAttachment) {
            $this->setFileName($this->emailAttachment->getFileName());
        }

        return $this;
    }

    public function getEmailTemplateAttachment(): ?EmailTemplateAttachmentModel
    {
        return $this->emailTemplateAttachment;
    }

    public function setEmailTemplateAttachment(?EmailTemplateAttachmentModel $emailTemplateAttachment): self
    {
        $this->emailTemplateAttachment = $emailTemplateAttachment;

        return $this;
    }

    /**
     * @param string $fileName
     *
     * @return $this
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return (string) $this->fileName;
    }

    /**
     * @return int
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * @param int $fileSize
     *
     * @return $this
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    /**
     * @return string
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * @param string $modified
     *
     * @return $this
     */
    public function setModified($modified)
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param UploadedFile $uploadedFile
     *
     * @return $this
     */
    public function setFile($uploadedFile)
    {
        $this->file = $uploadedFile;

        return $this;
    }

    /**
     * @param string $preview
     *
     * @return $this
     */
    public function setPreview($preview)
    {
        $this->preview = $preview;

        return $this;
    }

    /**
     * @return string
     */
    public function getPreview()
    {
        return $this->preview;
    }

    /**
     * @param string $icon
     *
     * @return $this
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * @param string $mimeType
     * @return $this
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     * @return $this
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;

        return $this;
    }

    public function addError(string $errorMessage)
    {
        $this->errors[] = $errorMessage;
    }
}
