<?php

namespace Oro\Bundle\EmailBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation as JMS;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileExtensionInterface;
use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;

/**
 * Email Attachment
 *
 * @ORM\Table(name="oro_email_attachment")
 * @ORM\Entity(repositoryClass="Oro\Bundle\EmailBundle\Entity\Repository\EmailAttachmentRepository")
 */
class EmailAttachment implements FileExtensionInterface
{
    const CLASS_NAME = 'Oro\Bundle\EmailBundle\Entity\EmailAttachment';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Soap\ComplexType("int")
     * @JMS\Type("integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="file_name", type="string", length=255)
     * @Soap\ComplexType("string")
     * @JMS\Type("string")
     */
    protected $fileName;

    /**
     * @var string
     *
     * @ORM\Column(name="content_type", type="string", length=100)
     * @Soap\ComplexType("string")
     * @JMS\Type("string")
     */
    protected $contentType;

    /**
     * @var EmailAttachmentContent
     *
     * @ORM\OneToOne(targetEntity="EmailAttachmentContent", mappedBy="emailAttachment",
     *      cascade={"persist", "remove"}, orphanRemoval=true)
     * @JMS\Exclude
     */
    protected $attachmentContent;

    /**
     * @var EmailBody
     *
     * @ORM\ManyToOne(targetEntity="EmailBody", inversedBy="attachments")
     * @ORM\JoinColumn(name="body_id", referencedColumnName="id")
     * @JMS\Exclude
     */
    protected $emailBody;

    /**
     * @var File
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AttachmentBundle\Entity\File")
     * @ORM\JoinColumn(name="file_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @JMS\Exclude
     */
    protected $file;

    /**
     * @var string
     *
     * @ORM\Column(name="embedded_content_id", type="string", length=255, nullable=true)
     * @Soap\ComplexType("string")
     * @JMS\Type("string")
     */
    protected $embeddedContentId;

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
     * @param File $file
     *
     * @return $this
     */
    public function setFile(File $file = null)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file extension
     *
     * @return string
     */
    public function getExtension()
    {
        return pathinfo($this->fileName, PATHINFO_EXTENSION);
    }

    /**
     * @return string
     */
    public function getEmbeddedContentId()
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
            $content = ContentDecoder::decode(
                $this->attachmentContent->getContent(),
                $this->attachmentContent->getContentTransferEncoding()
            );
            $size = strlen($content);
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
    public function __toString()
    {
        return (string)$this->getFileName();
    }
}
