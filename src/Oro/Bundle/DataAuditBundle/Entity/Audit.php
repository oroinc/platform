<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Audit model
 */
#[ORM\Entity]
#[Config(defaultValues: ['security' => []])]
class Audit extends AbstractAudit
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'logged_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $loggedAt = null;

    #[ORM\Column(name: 'object_id', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $objectId = null;

    #[ORM\Column(name: 'object_class', type: Types::STRING, length: 255)]
    protected ?string $objectClass = null;

    #[ORM\Column(name: 'object_name', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $objectName = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $version = null;

    /**
     * @var string $username
     */
    protected $username;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?User $user = null;

    #[ORM\Column(name: 'owner_description', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $ownerDescription = null;

    #[\Override]
    public function setUser(AbstractUser $user = null)
    {
        $this->user = $user;

        return $this;
    }

    #[\Override]
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get user name
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->getUser() ? $this->getUser()->getUserIdentifier() : '';
    }
}
