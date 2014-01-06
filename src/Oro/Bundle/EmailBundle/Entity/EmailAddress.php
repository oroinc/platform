<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Email Address
 * This class is dynamically extended based of email owner providers.
 * For details see
 *   - Resources/cache/Entity/EmailAddress.php.twig
 *   - Resources/cache/Entity/EmailAddress.orm.yml.twig
 *   - Cache/EmailAddressCacheWarmer.php
 *   - Cache/EmailAddressCacheClearer.php
 *   - Entity/Provider/EmailOwnerProviderStorage.php
 *   - DependencyInjection/Compiler/EmailOwnerConfigurationPass.php
 *   - OroEmailBundle.php
 *
 * @ORM\MappedSuperclass
 */
abstract class EmailAddress
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMS\Type("integer")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @JMS\Type("dateTime")
     */
    private $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @JMS\Type("dateTime")
     */
    private $updated;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     * @JMS\Type("string")
     */
    private $email;

    /**
     * @var bool
     *
     * @ORM\Column(name="has_owner", type="boolean")
     * @JMS\Type("boolean")
     */
    private $hasOwner = false;

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
     * Set id
     *
     * @param integer $id
     * @return EmailAddress
     */
    protected function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get entity created date/time
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created;
    }

    /**
     * Set entity created date/time
     *
     * @param \DateTime $createdAt
     * @return EmailAddress
     */
    protected function setCreatedAt($createdAt)
    {
        $this->created = $createdAt;

        return $this;
    }

    /**
     * Get entity updated date/time
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated;
    }

    /**
     * Set entity updated date/time
     *
     * @param \DateTime $updatedAt
     * @return EmailAddress
     */
    protected function setUpdatedAt($updatedAt)
    {
        $this->updated = $updatedAt;

        return $this;
    }

    /**
     * Get email address.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email address.
     *
     * @param string $email
     * @return EmailAddress
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Checks if this email address has an owner
     *
     * @return bool
     */
    public function hasOwner()
    {
        return $this->hasOwner;
    }

    /**
     * Sets a flag indicates whether this email address has an owner
     *
     * @param bool $hasOwner
     * @return EmailAddress
     */
    protected function setHasOwner($hasOwner)
    {
        $this->hasOwner = $hasOwner;

        return $this;
    }

    /**
     * Get email owner
     *
     * @return EmailOwnerInterface
     */
    abstract public function getOwner();

    /**
     * Set email owner
     *
     * @param EmailOwnerInterface|null $owner
     * @return EmailAddress
     */
    abstract public function setOwner(EmailOwnerInterface $owner = null);

    /**
     * Get a human-readable representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('EmailAddress(%s)', $this->email);
    }
}
