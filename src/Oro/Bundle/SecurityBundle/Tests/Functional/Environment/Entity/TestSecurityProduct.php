<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

#[ORM\Entity]
#[ORM\Table(name: 'test_security_product')]
#[Config(
    defaultValues: [
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'owner_id'
        ],
        'security' => ['type' => 'ACL', 'field_acl_supported' => true]
    ]
)]
class TestSecurityProduct implements TestFrameworkEntityInterface
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    protected ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Organization $owner = null;

    /**
     * @var Collection<int, TestSecurityOrder>
     */
    #[ORM\ManyToMany(targetEntity: TestSecurityOrder::class, mappedBy: 'products')]
    protected ?Collection $orders = null;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
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

    public function getOwner(): Organization
    {
        return $this->owner;
    }

    public function setOwner(Organization $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * @return TestSecurityOrder[]
     */
    public function getOrders(): array
    {
        return $this->orders;
    }

    /**
     * @param TestSecurityOrder[] $orders
     */
    public function setOrders(array $orders): void
    {
        $this->orders = $orders;
    }
}
