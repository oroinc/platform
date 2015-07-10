<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

/**
 * @ORM\Table(name="oro_mailbox")
 * @ORM\Entity
 *
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-envelope"
 *          }
 *      },
 *      ownership={
 *          "owner_type"="ORGANIZATION",
 *          "organization_field_name"="organization",
 *          "organization_column_name"="organization_id"
 *      },
 * )
 */
class Mailbox implements EmailOwnerInterface, EmailHolderInterface
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    protected $email;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Oro\Bundle\EmailBundle\Entity\MailboxEmail",
     *    mappedBy="owner", cascade={"all"}, orphanRemoval=true
     * )
     * @ORM\OrderBy({"primary" = "DESC"})
     */
    protected $emails;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255)
     */
    protected $label;

    /**
     * @var MailboxProcessor
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\EmailBundle\Entity\MailboxProcessor",
     *     cascade={"all"}, orphanRemoval=true
     * )
     * @ORM\JoinColumn(name="processor_id", referencedColumnName="id")
     */
    protected $processor;

    /**
     * @var EmailOrigin
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\EmailBundle\Entity\EmailOrigin",
     *     cascade={"all"}, orphanRemoval=true
     * )
     * @ORM\JoinColumn(name="origin_id", referencedColumnName="id")
     */
    protected $origin;

    /**
     * @var array
     *
     * @ORM\Column(name="smtp_settings", type="array")
     */
    protected $smtpSettings;

    /**
     * @var OrganizationInterface
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization", cascade={"remove"})
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $organization;

    public function __construct()
    {
        $this->smtpSettings = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return MailboxProcessor
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * @param MailboxProcessor $processor
     *
     * @return $this
     */
    public function setProcessor(MailboxProcessor $processor)
    {
        $this->processor = $processor;

        return $this;
    }

    /**
     * @return $this
     */
    public function clearProcessor()
    {
        $this->processor = null;

        return $this;
    }

    /**
     * @param mixed $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return __CLASS__;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailFields()
    {
        return ['email'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getLabel();
    }

    /**
     * @return array
     */
    public function getSmtpSettings()
    {
        return $this->smtpSettings;
    }

    /**
     * @param array $smtpSettings
     *
     * @return $this
     */
    public function setSmtpSettings($smtpSettings)
    {
        $this->smtpSettings = $smtpSettings;

        return $this;
    }

    /**
     * @return EmailOrigin
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * @param EmailOrigin $origin
     *
     * @return $this
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * @return OrganizationInterface
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param OrganizationInterface $organization
     *
     * @return $this
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Set emails.
     *
     * This method could not be named setEmails because of bug CRM-253.
     *
     * @param Collection|MailboxEmail[] $emails
     *
     * @return $this
     */
    public function resetEmails($emails)
    {
        $this->emails->clear();

        foreach ($emails as $email) {
            $this->addEmail($email);
        }

        return $this;
    }

    /**
     * Add email
     *
     * @param MailboxEmail $email
     *
     * @return $this
     */
    public function addEmail(MailboxEmail $email)
    {
        if (!$this->emails->contains($email)) {
            $this->emails->add($email);
            $email->setOwner($this);
        }

        return $this;
    }

    /**
     * Remove email
     *
     * @param MailboxEmail $email
     *
     * @return $this
     */
    public function removeEmail(MailboxEmail $email)
    {
        if ($this->emails->contains($email)) {
            $this->emails->removeElement($email);
        }

        return $this;
    }

    /**
     * Get emails
     *
     * @return Collection|MailboxEmail[]
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail()
    {
        $primaryEmail = $this->getPrimaryEmail();
        if (!$primaryEmail) {
            return null;
        }

        return $primaryEmail->getEmail();
    }

    /**
     * @param MailboxEmail $email
     *
     * @return bool
     */
    public function hasEmail(MailboxEmail $email)
    {
        return $this->getEmails()->contains($email);
    }

    /**
     * Gets primary email if it's available.
     *
     * @return MailboxEmail|null
     */
    public function getPrimaryEmail()
    {
        $result = null;

        foreach ($this->getEmails() as $email) {
            if ($email->isPrimary()) {
                $result = $email;
                break;
            }
        }

        return $result;
    }

    /**
     * @param MailboxEmail $email
     *
     * @return $this
     */
    public function setPrimaryEmail(MailboxEmail $email)
    {
        if ($this->hasEmail($email)) {
            $email->setPrimary(true);
            foreach ($this->getEmails() as $otherEmail) {
                if (!$email->isEqual($otherEmail)) {
                    $otherEmail->setPrimary(false);
                }
            }
        }

        return $this;
    }
}
