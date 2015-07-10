<?php

namespace Oro\Bundle\EmailBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EmailBundle\Model\ExtendMailboxEmail;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
/**
 * @ORM\Entity
 * @ORM\Table("oro_mailbox_email", indexes={
 *      @ORM\Index(name="primary_email_idx", columns={"email", "is_primary"})
 * })
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-envelope"
 *          },
 *          "note"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          }
 *      }
 * )
 */
class MailboxEmail extends ExtendMailboxEmail implements EmailInterface
{
    /**
     * @ORM\ManyToOne(targetEntity="Mailbox", inversedBy="emails")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $owner;

    /**
     * {@inheritdoc}
     */
    public function getEmailField()
    {
        return 'email';
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailOwner()
    {
        return $this->getOwner();
    }

    /**
     * Set contact as owner.
     *
     * @param Mailbox $owner
     */
    public function setOwner(Mailbox $owner = null)
    {
        $this->owner = $owner;
    }

    /**
     * Get owner contact.
     *
     * @return Mailbox
     */
    public function getOwner()
    {
        return $this->owner;
    }
}
