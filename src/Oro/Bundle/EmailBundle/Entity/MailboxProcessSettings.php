<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Form\Model\ExtendMailboxProcessSettings;

/**
 * @ORM\Table(
 *      name="oro_email_mailbox_process"
 * )
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=30)
 */
abstract class MailboxProcessSettings extends ExtendMailboxProcessSettings
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Mailbox
     * @ORM\OneToOne(targetEntity="Mailbox", mappedBy="processSettings")
     */
    protected $mailbox;

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

    /**
     * @param Mailbox $mailbox
     */
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
