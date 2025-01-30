<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileExtensionInterface;
use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailAttachmentRepository;

/**
 * Email Attachment
 */
#[ORM\Entity(repositoryClass: EmailAttachmentRepository::class)]
#[ORM\Table(name: 'oro_email_attachment')]
class EmailAttachment implements FileExtensionInterface
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'file_name', type: Types::STRING, length: 255)]
    protected ?string $fileName = null;

    #[ORM\Column(name: 'content_type', type: Types::STRING, length: 100)]
    protected ?string $contentType = null;

    #[ORM\OneToOne(
        mappedBy: 'emailAttachment',
        targetEntity: EmailAttachmentContent::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected ?EmailAttachmentContent $attachmentContent = null;

    #[ORM\ManyToOne(targetEntity: EmailBody::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'body_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?EmailBody $emailBody = null;

    #[ORM\ManyToOne(targetEntity: File::class)]
    #[ORM\JoinColumn(name: 'file_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?File $file = null;

    #[ORM\Column(name: 'embedded_content_id', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $embeddedContentId = null;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get attachment file name
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set attachment file name
     *
     * @param string $fileName
     * @return $this
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get content type. It may be any MIME type
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Set content type
     *
     * @param string $contentType any MIME type
     * @return $this
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * Get content of email attachment
     *
     * @return EmailAttachmentContent
     */
    public function getContent()
    {
        return $this->attachmentContent;
    }

    /**
     * Set content of email attachment
     *
     * @param  EmailAttachmentContent $attachmentContent
     * @return $this
     */
    public function setContent(EmailAttachmentContent $attachmentContent)
    {
        $this->attachmentContent = $attachmentContent;

        $attachmentContent->setEmailAttachment($this);

        return $this;
    }

    /**
     * Get email body
     *
     * @return EmailBody
     */
    public function getEmailBody()
    {
        return $this->emailBody;
    }

    /**
     * Set email body
     *
     * @param EmailBody $emailBody
     * @return $this
     */
    public function setEmailBody(EmailBody $emailBody)
    {
        $this->emailBody = $emailBody;

        return $this;
    }

    /**
     * Get attachment file
     *
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set email attachment
     *
     * @param File|null $file
     *
     * @return $this
     */
    public function setFile(?File $file = null)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file extension
     *
     * @return string
     */
    #[\Override]
    public function getExtension()
    {
        return pathinfo($this->fileName, PATHINFO_EXTENSION);
    }

    public function getEmbeddedContentId(): ?string
    {
        return $this->embeddedContentId;
    }

    /**
     * @param string $embeddedContentId
     * @return $this
     */
    public function setEmbeddedContentId($embeddedContentId)
    {
        $this->embeddedContentId = $embeddedContentId;

        return $this;
    }

    /**
     * Get size of attachment
     *
     * @return int
     */
    public function getSize()
    {
        if ($this->file) {
            $size = $this->file->getFileSize();
        } else {
            $size = 0;
            if ($this->attachmentContent?->getContent() !== null) {
                $content = ContentDecoder::decode(
                    $this->attachmentContent->getContent(),
                    $this->attachmentContent->getContentTransferEncoding()
                );
                $size = strlen($content);
            }
        }

        return $size;
    }

    /**
     * Clone record as new one
     */
    public function __clone()
    {
        $this->id = null;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getFileName();
    }
}
