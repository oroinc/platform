<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    protected ?string $username = null;

    #[ORM\ManyToOne(targetEntity: BusinessUnit::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'business_unit_owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?BusinessUnit $owner = null;

    /**
     * @var Collection<int, BusinessUnit>
     */
    #[ORM\ManyToMany(targetEntity: BusinessUnit::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'oro_user_business_unit')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'business_unit_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $businessUnits = null;

    /**
     * @var Collection<int, Organization>
     */
    #[ORM\ManyToMany(targetEntity: Organization::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'oro_user_organization')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $organizations = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    /**
     * @var Collection<int, Group>
     */
    #[ORM\ManyToMany(targetEntity: Group::class)]
    #[ORM\JoinTable(name: 'oro_user_access_group')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'group_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $groups = null;

    /**
     * @var Collection<int, Role>
     */
    #[ORM\ManyToMany(targetEntity: Role::class)]
    #[ORM\JoinTable(name: 'oro_user_access_role')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'role_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $roles = null;

    /**
     * @var Collection<int, UserApi>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserApi::class, cascade: ['persist', 'remove'])]
    protected ?Collection $apiKeys = null;

    /**
     * @var Collection<int, Status>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Status::class)]
    #[ORM\OrderBy(['createdAt' => Criteria::DESC])]
    protected ?Collection $statuses = null;

    /**
     * @var Collection<int, EmailOrigin>
     */
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: EmailOrigin::class, cascade: ['persist', 'remove'])]
    protected ?Collection $emailOrigins = null;

    /**
     * @var Collection<int, UserEmail>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserEmail::class, cascade: ['persist'], orphanRemoval: true)]
    protected ?Collection $emails = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $createdAt = null;
}
