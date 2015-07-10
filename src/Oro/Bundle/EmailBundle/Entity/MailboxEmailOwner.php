<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 */
class MailboxEmailOwner extends EmailOwner
{
    /**
     * @var Mailbox
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\EmailBundle\Entity\Mailbox")
     * @ORM\JoinColumn(name="mailbox_owner_id", referencedColumnName="id", onDelete="SET NULL")
     * @JMS\Exclude
     */
    protected $mailboxOwner;

    /**
     * Get owning mailbox
     *
     * @return Mailbox
     */
    public function getMailboxOwner()
    {
        return $this->mailboxOwner;
    }

    /**
     * Set owning mailbox
     *
     * @param Mailbox $mailbox
     *
     * @return $this
     */
    public function setMailboxOwner($mailbox)
    {
        $this->mailboxOwner = $mailbox;

        return $this;
    }
}
