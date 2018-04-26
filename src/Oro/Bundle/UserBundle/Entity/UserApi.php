<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Security\UserApiKeyInterface;

/**
 * The entity that represents API access keys for users.
 *
 * @ORM\Table(name="oro_user_api")
 * @ORM\Entity(repositoryClass="Oro\Bundle\UserBundle\Entity\Repository\UserApiRepository")
 */
class UserApi implements UserApiKeyInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User", inversedBy="apiKeys", fetch="LAZY")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(name="api_key", type="crypted_string", unique=true, length=255, nullable=false)
     */
    protected $apiKey;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * Gets unique identifier of this entity.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Indicates whether this API key is enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getUser()->getOrganizations()->contains($this->getOrganization());
    }

    /**
     * Sets API key.
     *
     * @param string $apiKey
     *
     * @return UserApi
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Gets API key.
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Sets a user this API key belongs to.
     *
     * @param User $user
     *
     * @return UserApi
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Gets a user this API key belongs to.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Generates random API key.
     *
     * @return string
     */
    public function generateKey()
    {
        return bin2hex(random_bytes(20));
    }

    /**
     * Sets an organization this API key belongs to.
     *
     * @param Organization $organization
     *
     * @return UserApi
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Gets an organization this API key belongs to.
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getId();
    }
}
