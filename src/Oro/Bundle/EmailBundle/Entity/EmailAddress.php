<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

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
 */
#[ORM\MappedSuperclass]
abstract class EmailAddress
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'created', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created = null;

    #[ORM\Column(name: 'updated', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updated = null;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 255)]
    private ?string $email = null;

    #[ORM\Column(name: 'has_owner', type: Types::BOOLEAN)]
    private ?bool $hasOwner = false;

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
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set entity created date/time
     *
     * @param \DateTime $created
     * @return EmailAddress
     */
    protected function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get entity updated date/time
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set entity updated date/time
     *
     * @param \DateTime $updated
     * @return EmailAddress
     */
    protected function setUpdated($updated)
    {
        $this->updated = $updated;

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
    public function getHasOwner()
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
