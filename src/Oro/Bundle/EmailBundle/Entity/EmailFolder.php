<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\Common\Collections\ArrayCollection;

use JMS\Serializer\Annotation as JMS;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

/**
 * Email Folder
 *
 * @ORM\Table(
 *      name="oro_email_folder",
 *      indexes={@Index(name="email_folder_outdated_at_idx", columns={"outdated_at"})}
 * )
 * @ORM\Entity
 */
class EmailFolder
{
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
     * @ORM\Column(name="name", type="string", length=255)
     * @Soap\ComplexType("string")
     * @JMS\Type("string")
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="full_name", type="string", length=255)
     * @Soap\ComplexType("string")
     * @JMS\Type("string")
     */
    protected $fullName;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=10)
     * @Soap\ComplexType("string")
     * @JMS\Type("string")
     */
    protected $type;

    /**
     * @var EmailOrigin
     *
     * @ORM\ManyToOne(targetEntity="EmailOrigin", inversedBy="folders")
     * @ORM\JoinColumn(name="origin_id", referencedColumnName="id")
     * @JMS\Exclude
     */
    protected $origin;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Email", mappedBy="folders", cascade={"persist"}, orphanRemoval=true)
     * @JMS\Exclude
     */
    protected $emails;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="synchronized", type="datetime", nullable=true)
     */
    protected $synchronizedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="outdated_at", type="datetime", nullable=true)
     */
    protected $outdatedAt;

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
     * Get folder name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set folder name
     *
     * @param string $name
     *
     * @return EmailFolder
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get full name of this folder
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * Set full name of this folder
     *
     * @param string $fullName
     *
     * @return EmailFolder
     */
    public function setFullName($fullName)
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * Get folder type.
     *
     * @return string Can be 'inbox', 'sent', 'trash', 'drafts' or 'other'
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set folder type
     *
     * @param string $type One of FolderType constants
     *
     * @return EmailFolder
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get email folder origin
     *
     * @return EmailOrigin
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * Set email folder origin
     *
     * @param EmailOrigin $origin
     *
     * @return EmailFolder
     */
    public function setOrigin(EmailOrigin $origin)
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * Get emails
     *
     * @return Email[]
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * Add email
     *
     * @param Email $email
     *
     * @return EmailFolder
     */
    public function addEmail(Email $email)
    {
        if (!$this->emails->contains($email)) {
            $this->emails->add($email);
        }

        return $this;
    }

    /**
     * @param Email $email
     *
     * @return EmailFolder
     */
    public function removeEmail(Email $email)
    {
        if ($this->emails->contains($email)) {
            $this->emails->removeElement($email);
        }

        return $this;
    }

    /**
     * Get date/time when emails in this folder were synchronized
     *
     * @return \DateTime
     */
    public function getSynchronizedAt()
    {
        return $this->synchronizedAt;
    }

    /**
     * Set date/time when emails in this folder were synchronized
     *
     * @param \DateTime $synchronizedAt
     *
     * @return EmailOrigin
     */
    public function setSynchronizedAt($synchronizedAt)
    {
        $this->synchronizedAt = $synchronizedAt;

        return $this;
    }

    /**
     * @param \DateTime $outdatedAt
     *
     * @return EmailFolder
     */
    public function setOutdatedAt($outdatedAt = null)
    {
        $this->outdatedAt = $outdatedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getOutdatedAt()
    {
        return $this->outdatedAt;
    }

    /**
     * @return bool
     */
    public function isOutdated()
    {
        return $this->outdatedAt !== null;
    }

    /**
     * Get a human-readable representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('EmailFolder(%s)', $this->fullName);
    }
}
