<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Mailbox Process Settings
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_email_mailbox_process')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string', length: 30)]
abstract class MailboxProcessSettings
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\OneToOne(mappedBy: 'processSettings', targetEntity: Mailbox::class)]
    protected ?Mailbox $mailbox = null;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns type of process.
     *
     * @return string
     */
    abstract public function getType();

    /**
     * @return Mailbox
     */
    public function getMailbox()
    {
        return $this->mailbox;
    }

    public function setMailbox(Mailbox $mailbox = null)
    {
        $this->mailbox = $mailbox;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getId();
    }
}
