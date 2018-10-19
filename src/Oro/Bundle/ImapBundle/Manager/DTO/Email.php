<?php

namespace Oro\Bundle\ImapBundle\Manager\DTO;

use Oro\Bundle\EmailBundle\Model\EmailHeader;
use Oro\Bundle\ImapBundle\Mail\Storage\Body;
use Oro\Bundle\ImapBundle\Mail\Storage\Message;

/**
 * Represents IMAP email instance with content like body and attachments.
 */
class Email extends EmailHeader
{
    /**
     * @var Message
     */
    protected $message;

    /**
     * @var ItemId
     */
    protected $id;

    /**
     * @var EmailBody
     */
    protected $body = null;

    /**
     * @var EmailAttachment[]
     */
    protected $attachments;

    /**
     * Constructor
     *
     * @param Message $message
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get item id
     *
     * @return ItemId
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set item id
     *
     * @param ItemId $id
     *
     * @return self
     */
    public function setId(ItemId $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get Message object
     *
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get email body
     *
     * @return EmailBody
     */
    public function getBody()
    {
        if ($this->body === null) {
            $this->body = new EmailBody();
            $body = $this->message->getBody();

            $contentType = $this->message->getPriorContentType();
            if ($contentType && strtolower($contentType->getType()) === 'text/html') {
                $this->body->setContent($body->getContent(Body::FORMAT_HTML)->getDecodedContent());
                $this->body->setBodyIsText(false);
            } else {
                $this->body->setContent($body->getContent(Body::FORMAT_TEXT)->getDecodedContent());
                $this->body->setBodyIsText(true);
            }

            $this->body->setOriginalContentType($contentType);
        }

        return $this->body;
    }

    /**
     * Get email attachments
     *
     * @return EmailAttachment[]
     */
    public function getAttachments()
    {
        if ($this->attachments === null) {
            $this->attachments = [];
            $attachments = $this->message->getAttachments();
            if (!$attachments && !$this->getBody()->getOriginalContentType()) {
                $attachment = $this->message->getMessageAsAttachment();
                if ($attachment) {
                    $attachments[] = $attachment;
                }
            }

            foreach ($attachments as $a) {
                $fileSize = $a->getFileSize();
                $content  = $a->getContent();
                $filename = $a->getFileName()->getValue();
                if ($filename !== null) {
                    $attachment = new EmailAttachment();
                    $attachment
                        ->setFileName($filename)
                        ->setFileSize($fileSize)
                        ->setContent($content->getContent())
                        ->setContentType($content->getContentType())
                        ->setContentTransferEncoding($content->getContentTransferEncoding())
                        ->setContentId($a->getEmbeddedContentId());
                    $this->attachments[] = $attachment;
                }
            }
        }

        return $this->attachments;
    }

    /**
     * Check exists flag
     *
     * @param string $flag
     *
     * @return bool
     */
    public function hasFlag($flag)
    {
        $flags = $this->message->getFlags();
        if (in_array($flag, $flags, true)) {
            return true;
        }
        return false;
    }
}
