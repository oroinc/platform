<?php

namespace Oro\Bundle\EmailBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use JMS\Serializer\Annotation as JMS;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * EmailThread
 *
 * @ORM\Table(
 *      name="oro_email_thread"
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class EmailThread
{
    const ENTITY_CLASS = 'Oro\Bundle\EmailBundle\Entity\EmailThread';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMS\Type("integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=500)
     * @JMS\Type("string")
     */
    protected $subject;

    /**
     * @var ArrayCollection|Email[] $emails
     *
     * @ORM\OneToMany(targetEntity="Email", mappedBy="thread", cascade={"persist", "remove"}, orphanRemoval=true)
     * @JMS\Exclude
     */
    protected $emails;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sent", type="datetime")
     * @JMS\Type("DateTime")
     */
    protected $sentAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @JMS\Type("dateTime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $created;

    public function __construct()
    {
        $this->emails = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get entity created date/time
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get email subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set email subject
     *
     * @param string $subject
     *
     * @return Email
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get date/time when email sent
     *
     * @return \DateTime
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * Set date/time when email sent
     *
     * @param \DateTime $sentAt
     *
     * @return Email
     */
    public function setSentAt($sentAt)
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    /**
     * @param Email $email
     *
     * @return bool
     */
    public function hasEmail(Email $email)
    {
        return $this->emails->contains($email);
    }

    /**
     * @param Email $email
     *
     * @return Email
     */
    public function removeEmail(Email $email)
    {
        if ($this->emails->contains($email)) {
            $this->emails->removeElement($email);
        }

        return $this;
    }

    /**
     * Get email
     *
     * @return ArrayCollection|Email[]
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * Set email
     *
     * @param Email $email
     *
     * @return Email
     */
    public function addEmail(Email $email)
    {
        if (!$this->emails->contains($email)) {
            $this->emails->add($email);
        }

        return $this;
    }

    /**
     * Pre persist event listener
     *
     * @ORM\PrePersist
     */
    public function beforeSave()
    {
        $this->created = new \DateTime('now', new \DateTimeZone('UTC'));
    }

}
