<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

#[ORM\Entity]
#[ORM\Table(name: 'test_security_department')]
#[Config(
    defaultValues: [
        'ownership' => [
            'owner_type' => 'BUSINESS_UNIT',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'business_unit_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'field_acl_supported' => true]
    ]
)]
class TestSecurityDepartment implements TestFrameworkEntityInterface
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    protected ?string $name = null;

    /**
     * @var Collection<int, TestSecurityPerson>
     */
    #[ORM\OneToMany(mappedBy: 'department', targetEntity: TestSecurityPerson::class)]
    protected ?Collection $staff = null;

    #[ORM\ManyToOne(targetEntity: TestSecurityCompany::class, inversedBy: 'departments')]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?TestSecurityCompany $company = null;

    #[ORM\ManyToOne(targetEntity: BusinessUnit::class)]
    #[ORM\JoinColumn(name: 'business_unit_owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?BusinessUnit $owner = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    public function __construct()
    {
        $this->staff = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return TestSecurityDepartment[]
     */
    public function getStaff(): array
    {
        return $this->staff;
    }

    /**
     * @param TestSecurityDepartment[] $staff
     */
    public function setStaff(array $staff): void
    {
        $this->staff = $staff;
    }

    public function getCompany(): TestSecurityCompany
    {
        return $this->company;
    }

    public function setCompany(TestSecurityCompany $company): void
    {
        $this->company = $company;
    }

    public function getOwner(): BusinessUnit
    {
        return $this->owner;
    }

    public function setOwner(BusinessUnit $owner): void
    {
        $this->owner = $owner;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): void
    {
        $this->organization = $organization;
    }
}
