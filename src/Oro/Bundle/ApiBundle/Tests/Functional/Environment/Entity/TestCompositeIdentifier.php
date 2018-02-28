<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Table(name="test_api_composite_id")
 * @ORM\Entity
 */
class TestCompositeIdentifier implements TestFrameworkEntityInterface
{
    /**
     * @var string
     *
     * @ORM\Column(name="key1", type="string", nullable=false)
     * @ORM\Id
     */
    public $key1;

    /**
     * @var int
     *
     * @ORM\Column(name="key2", type="integer", nullable=false)
     * @ORM\Id
     */
    public $key2;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=true)
     */
    public $name;

    /**
     * @var TestCompositeIdentifier|null
     *
     * @ORM\ManyToOne(targetEntity="TestCompositeIdentifier")
     * @ORM\JoinColumns({
     *      @ORM\JoinColumn(name="parent_key1", referencedColumnName="key1"),
     *      @ORM\JoinColumn(name="parent_key2", referencedColumnName="key2")
     * })
     */
    protected $parent;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="TestCompositeIdentifier")
     * @ORM\JoinTable(name="test_api_composite_id_children",
     *      joinColumns={
     *          @ORM\JoinColumn(name="parent_key1", referencedColumnName="key1"),
     *          @ORM\JoinColumn(name="parent_key2", referencedColumnName="key2")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="child_key1", referencedColumnName="key1"),
     *          @ORM\JoinColumn(name="child_key2", referencedColumnName="key2")
     *      }
     * )
     */
    protected $children;

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

    /**
     * @param TestCompositeIdentifier $item
     */
    public function addChild(TestCompositeIdentifier $item)
    {
        $this->children->add($item);
    }

    /**
     * @param TestCompositeIdentifier $item
     */
    public function removeChild(TestCompositeIdentifier $item)
    {
        $this->children->removeElement($item);
    }
}
