<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

#[ORM\Entity]
#[ORM\Table(name: 'test_api_custom_composite_id')]
#[ORM\UniqueConstraint(name: 'test_api_custom_composite_idx', columns: ['key1', 'key2'])]
class TestCustomCompositeIdentifier implements TestFrameworkEntityInterface
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\Column(name: 'key1', type: Types::STRING, nullable: false)]
    public ?string $key1 = null;

    #[ORM\Column(name: 'key2', type: Types::INTEGER, nullable: false)]
    public ?int $key2 = null;

    #[ORM\Column(name: 'name', type: Types::STRING, nullable: true)]
    public ?string $name = null;

    #[ORM\ManyToOne(targetEntity: TestCustomCompositeIdentifier::class)]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    protected ?TestCustomCompositeIdentifier $parent = null;

    /**
     * @var Collection<int, TestCustomCompositeIdentifier>
     */
    #[ORM\ManyToMany(targetEntity: TestCustomCompositeIdentifier::class)]
    #[ORM\JoinTable(name: 'test_api_custom_composite_id_c')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'child_id', referencedColumnName: 'id')]
    protected ?Collection $children = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * @return TestCustomCompositeIdentifier|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param TestCustomCompositeIdentifier $item
     */
    public function setParent($item)
    {
        $this->parent = $item;
    }

    /**
     * @return Collection|TestCustomCompositeIdentifier[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function addChild(TestCustomCompositeIdentifier $item)
    {
        $this->children->add($item);
    }

    public function removeChild(TestCustomCompositeIdentifier $item)
    {
        $this->children->removeElement($item);
    }
}
