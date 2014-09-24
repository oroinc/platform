<?php

namespace Oro\Bundle\ImapBundle\Manager\DTO;

use Oro\Bundle\EmailBundle\Model\EmailHeader;
use Oro\Bundle\ImapBundle\Mail\Storage\Body;
use Oro\Bundle\ImapBundle\Mail\Storage\Message;
use Oro\Bundle\ImapBundle\Mail\Storage\Exception\InvalidBodyFormatException;

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
     * Get email body
     *
     * @return EmailBody
     */
    public function getBody()
    {
        if ($this->body === null) {
            $this->body = new EmailBody();

            $body = $this->message->getBody();
            try {
                $this->body->setContent($body->getContent(Body::FORMAT_HTML)->getDecodedContent());
                $this->body->setBodyIsText(false);
            } catch (InvalidBodyFormatException $ex) {
                $this->body->setContent($body->getContent(Body::FORMAT_TEXT)->getDecodedContent());
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

            foreach ($this->message->getAttachments() as $a) {
                $content    = $a->getContent();
                $attachment = new EmailAttachment();
                $attachment
                    ->setFileName($a->getFileName()->getDecodedValue())
                    ->setContent($content->getContent())
                    ->setContentType($content->getContentType())
                    ->setContentTransferEncoding($content->getContentTransferEncoding());
                $this->attachments[] = $attachment;
            }
        }

        return $this->attachments;
    }
}
