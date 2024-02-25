<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

#[ORM\Entity]
#[ORM\Table(name: 'test_api_composite_id')]
class TestCompositeIdentifier implements TestFrameworkEntityInterface
{
    #[ORM\Column(name: 'key1', type: Types::STRING, nullable: false)]
    #[ORM\Id]
    public ?string $key1 = null;

    #[ORM\Column(name: 'key2', type: Types::INTEGER, nullable: false)]
    #[ORM\Id]
    public ?int $key2 = null;

    #[ORM\Column(name: 'name', type: Types::STRING, nullable: true)]
    public ?string $name = null;

    #[ORM\JoinColumn(name: 'parent_key1', referencedColumnName: 'key1')]
    #[ORM\JoinColumn(name: 'parent_key2', referencedColumnName: 'key2')]
    #[ORM\ManyToOne(targetEntity: TestCompositeIdentifier::class)]
    protected ?TestCompositeIdentifier $parent = null;

    /**
     * @var Collection<int, TestCompositeIdentifier>
     */
    #[ORM\ManyToMany(targetEntity: TestCompositeIdentifier::class)]
    #[ORM\JoinTable(name: 'test_api_composite_id_children')]
    #[ORM\JoinColumn(name: 'parent_key1', referencedColumnName: 'key1')]
    #[ORM\JoinColumn(name: 'parent_key2', referencedColumnName: 'key2')]
    #[ORM\InverseJoinColumn(name: 'child_key1', referencedColumnName: 'key1')]
    #[ORM\InverseJoinColumn(name: 'child_key2', referencedColumnName: 'key2')]
    protected ?Collection $children = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * @return TestCompositeIdentifier|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param TestCompositeIdentifier $item
     */
    public function setParent($item)
    {
        $this->parent = $item;
    }

    /**
     * @return Collection|TestCompositeIdentifier[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function addChild(TestCompositeIdentifier $item)
    {
        $this->children->add($item);
    }

    public function removeChild(TestCompositeIdentifier $item)
    {
        $this->children->removeElement($item);
    }
}
