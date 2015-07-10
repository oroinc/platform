<?php

namespace Oro\Bundle\EmailBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Table(
 *      name="oro_email_owner"
 * )
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="name", type="string", length=30)
 */
class EmailOwner
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Soap\ComplexType("int")
     * @JMS\Type("integer")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @JMS\Type("dateTime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="received", type="datetime")
     * @Soap\ComplexType("dateTime")
     * @JMS\Type("dateTime")
     */
    protected $receivedAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_seen", type="boolean", options={"default"=true})
     * @Soap\ComplexType("boolean")
     * @JMS\Type("boolean")
     */
    protected $seen = false;

    /**
     * @var EmailFolder $folder
     *
     * @ORM\ManyToOne(targetEntity="EmailFolder", inversedBy="emailUsers", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="folder_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @JMS\Exclude
     */
    protected $folder;

    /**
     * @var Email $email
     *
     * @ORM\ManyToOne(targetEntity="Email", inversedBy="emailUsers", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="email_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @JMS\Exclude
     */
    protected $email;

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
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Get date/time when email received
     *
     * @return \DateTime
     */
    public function getReceivedAt()
    {
        return $this->receivedAt;
    }

    /**
     * Set date/time when email received
     *
     * @param \DateTime $receivedAt
     *
     * @return $this
     */
    public function setReceivedAt($receivedAt)
    {
        $this->receivedAt = $receivedAt;

        return $this;
    }

    /**
     * Get if email is seen
     *
     * @return bool
     */
    public function isSeen()
    {
        return $this->seen;
    }

    /**
     * Set email is read flag
     *
     * @param boolean $seen
     *
     * @return $this
     */
    public function setSeen($seen)
    {
        $this->seen = (bool)$seen;

        return $this;
    }

    /**
     * Get email folder
     *
     * @return EmailFolder
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * @param EmailFolder $folder
     *
     * @return $this
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * Get email
     *
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email
     *
     * @param Email $email
     *
     * @return $this
     */
    public function setEmail(Email $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Pre persist event listener
     *
     * @ORM\PrePersist
     */
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
