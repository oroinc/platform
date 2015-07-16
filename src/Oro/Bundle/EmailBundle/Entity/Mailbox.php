<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

/**
 * @ORM\Table(name="oro_email_mailbox")
 * @ORM\Entity(repositoryClass="Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository")
 *
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-envelope"
 *          },
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          }
 *      }
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
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * @var AutoresponseRule[]|Collection
     *
     * @ORM\OneToMany(targetEntity="AutoresponseRule", mappedBy="mailbox")
     */
    protected $autoresponseRules;

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
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
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
     * @return AutoresponseRule[]|Collection
     */
    public function getAutoresponseRules()
    {
        return $this->autoresponseRules;
    }
}
