<?php

namespace Oro\Bundle\EmailBundle\Form\Model;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment as EmailAttachmentEntity;

class EmailAttachment
{
    const TYPE_ATTACHMENT       = 1; // oro attachment (OroAttachmentBundle)
    const TYPE_EMAIL_ATTACHMENT = 2; // email attachment

    /**
     * @var int
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
     * @var EmailAttachment
     */
    protected $emailAttachment;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
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
        $this->setFileName($this->emailAttachment->getFileName());

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
}
