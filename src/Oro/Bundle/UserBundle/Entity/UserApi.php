<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\Repository\UserApiRepository;
use Oro\Bundle\UserBundle\Security\UserApiKeyInterface;

/**
 * The entity that represents API access keys for users.
 */
#[ORM\Entity(repositoryClass: UserApiRepository::class)]
#[ORM\Table(name: 'oro_user_api')]
class UserApi implements UserApiKeyInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'LAZY', inversedBy: 'apiKeys')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?User $user = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'api_key', type: 'crypted_string', length: 255, unique: true, nullable: false)]
    protected $apiKey;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

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
        return $this->getUser()->isBelongToOrganization($this->getOrganization());
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
     * @param Organization|null $organization
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
