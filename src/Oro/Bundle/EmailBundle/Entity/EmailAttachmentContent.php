<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Email Attachment
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_email_attachment_content')]
class EmailAttachmentContent
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'attachmentContent', targetEntity: EmailAttachment::class)]
    #[ORM\JoinColumn(name: 'attachment_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?EmailAttachment $emailAttachment = null;

    #[ORM\Column(name: 'content', type: Types::TEXT, nullable: false)]
    protected ?string $content = null;

    #[ORM\Column(name: 'content_transfer_encoding', type: Types::STRING, length: 20, nullable: false)]
    protected ?string $contentTransferEncoding = null;

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
     * Get email attachment owner
     *
     * @return EmailAttachment
     */
    public function getEmailAttachment()
    {
        return $this->emailAttachment;
    }

    /**
     * Set email attachment owner
     *
     * @param EmailAttachment $emailAttachment
     * @return $this
     */
    public function setEmailAttachment(EmailAttachment $emailAttachment)
    {
        $this->emailAttachment = $emailAttachment;

        return $this;
    }

    /**
     * Get attachment content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set attachment content
     *
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get encoding type of attachment content
     *
     * @return string
     */
    public function getContentTransferEncoding()
    {
        return $this->contentTransferEncoding;
    }

    /**
     * Set encoding type of attachment content
     *
     * @param string $contentTransferEncoding
     * @return $this
     */
    public function setContentTransferEncoding($contentTransferEncoding)
    {
        $this->contentTransferEncoding = $contentTransferEncoding;

        return $this;
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
    public function __toString()
    {
        return (string)$this->getId();
    }
}
