<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

#[ORM\Entity]
#[ORM\Table(name: 'test_api_unique_key_id')]
#[ORM\Index(columns: ['key5'], name: 'test_api_unique_key5_idx')]
#[ORM\UniqueConstraint(name: 'test_api_unique_key_idx', columns: ['key1', 'key2'])]
#[Config(
    defaultValues: [
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'organization',
            'owner_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '']
    ]
)]
class TestUniqueKeyIdentifier implements TestFrameworkEntityInterface
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\Column(name: 'key1', type: Types::STRING, nullable: false)]
    public ?string $key1 = null;

    #[ORM\Column(name: 'key2', type: Types::INTEGER, nullable: false)]
    public ?int $key2 = null;

    #[ORM\Column(name: 'key3', type: Types::STRING, unique: true, nullable: false)]
    public ?string $key3 = null;

    #[ORM\Column(name: 'key4', type: Types::INTEGER, unique: true, nullable: false)]
    public ?int $key4 = null;

    #[ORM\Column(name: 'key5', type: Types::STRING, nullable: true)]
    public ?string $key5 = null;

    #[ORM\Column(name: 'key6', type: Types::STRING, nullable: true)]
    public ?string $key6 = null;

    #[ORM\Column(name: 'key7', type: Types::STRING, nullable: true)]
    public ?string $key7 = null;

    #[ORM\Column(name: 'name', type: Types::STRING, nullable: true)]
    public ?string $name = null;

    #[ORM\ManyToOne(targetEntity: TestUniqueKeyIdentifier::class)]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    protected ?TestUniqueKeyIdentifier $parent = null;

    /**
     * @var Collection<int, TestUniqueKeyIdentifier>
     */
    #[ORM\ManyToMany(targetEntity: TestUniqueKeyIdentifier::class)]
    #[ORM\JoinTable(name: 'test_api_unique_key_id_children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'child_id', referencedColumnName: 'id')]
    protected ?Collection $children = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * @return TestUniqueKeyIdentifier|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param TestUniqueKeyIdentifier $item
     */
    public function setParent($item)
    {
        $this->parent = $item;
    }

    /**
     * @return Collection<int, TestUniqueKeyIdentifier>
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function addChild(TestUniqueKeyIdentifier $item)
    {
        $this->children->add($item);
    }

    public function removeChild(TestUniqueKeyIdentifier $item)
    {
        $this->children->removeElement($item);
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization)
    {
        $this->organization = $organization;
    }
}
