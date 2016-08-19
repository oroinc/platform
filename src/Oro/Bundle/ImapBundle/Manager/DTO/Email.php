<?php

namespace Oro\Bundle\ImapBundle\Manager\DTO;

use Oro\Bundle\EmailBundle\Model\EmailHeader;
use Oro\Bundle\ImapBundle\Mail\Storage\Body;
use Oro\Bundle\ImapBundle\Mail\Storage\Message;
use Oro\Bundle\ImapBundle\Mail\Storage\Exception\InvalidBodyFormatException;

class Email extends EmailHeader
{
    const EMAIL_EMPTY_BODY_CONTENT = "\n";

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
            } elseif ($contentType && strtolower($contentType->getType()) === 'text/plain') {
                $this->body->setContent($body->getContent(Body::FORMAT_TEXT)->getDecodedContent());
                $this->body->setBodyIsText(true);
            } else {
                //if body has wrong type, set body as empty and then try to save it as attachment
                $this->body->setContent(self::EMAIL_EMPTY_BODY_CONTENT);
                $this->body->setBodyIsText(true);
            }
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
            $this->attachments = array();

            if ($this->getBody()->getContent() === self::EMAIL_EMPTY_BODY_CONTENT) {
                $attachment = $this->message->getMessageAsAttachment();
                $attachments = $attachment === null ? [] : [$attachment];
            } else {
                $attachments = $this->message->getAttachments();
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
        if (in_array($flag, $flags)) {
            return true;
        }
        return false;
    }
}
