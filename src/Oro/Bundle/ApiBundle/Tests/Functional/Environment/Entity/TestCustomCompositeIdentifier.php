<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Table(name="test_api_custom_composite_id")
 * @ORM\Entity
 */
class TestCustomCompositeIdentifier implements TestFrameworkEntityInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="key1", type="string", nullable=false)
     */
    public $key1;

    /**
     * @var int
     *
     * @ORM\Column(name="key2", type="integer", nullable=false)
     */
    public $key2;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=true)
     */
    public $name;

    /**
     * @var TestCustomCompositeIdentifier|null
     *
     * @ORM\ManyToOne(targetEntity="TestCustomCompositeIdentifier")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parent;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="TestCustomCompositeIdentifier")
     * @ORM\JoinTable(name="test_api_custom_composite_id_c",
     *      joinColumns={@ORM\JoinColumn(name="parent_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="child_id", referencedColumnName="id")}
     * )
     */
    protected $children;

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

    /**
     * @param TestCustomCompositeIdentifier $item
     */
    public function addChild(TestCustomCompositeIdentifier $item)
    {
        $this->children->add($item);
    }

    /**
     * @param TestCustomCompositeIdentifier $item
     */
    public function removeChild(TestCustomCompositeIdentifier $item)
    {
        $this->children->removeElement($item);
    }
}
