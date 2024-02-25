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
#[ORM\Table(name: 'test_api_custom_int_id')]
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
class TestCustomIntIdentifier implements TestFrameworkEntityInterface
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\Column(name: '`key`', type: Types::INTEGER, unique: true, nullable: false)]
    public ?int $key = null;

    #[ORM\Column(name: 'name', type: Types::STRING, nullable: true)]
    public ?string $name = null;

    #[ORM\ManyToOne(targetEntity: TestCustomIntIdentifier::class)]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    protected ?TestCustomIntIdentifier $parent = null;

    /**
     * @var Collection<int, TestCustomIntIdentifier>
     */
    #[ORM\ManyToMany(targetEntity: TestCustomIntIdentifier::class)]
    #[ORM\JoinTable(name: 'test_api_custom_int_id_children')]
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
     * @return TestCustomIntIdentifier|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param TestCustomIntIdentifier $item
     */
    public function setParent($item)
    {
        $this->parent = $item;
    }

    /**
     * @return Collection<int, TestCustomIntIdentifier>
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function addChild(TestCustomIntIdentifier $item)
    {
        $this->children->add($item);
    }

    public function removeChild(TestCustomIntIdentifier $item)
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
