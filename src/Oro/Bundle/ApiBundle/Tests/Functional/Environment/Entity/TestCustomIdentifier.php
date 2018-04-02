<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Table(name="test_api_custom_id")
 * @ORM\Entity
 */
class TestCustomIdentifier implements TestFrameworkEntityInterface
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
     * @ORM\Column(name="`key`", type="string", nullable=false)
     */
    public $key;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=true)
     */
    public $name;

    /**
     * @var TestCustomIdentifier|null
     *
     * @ORM\ManyToOne(targetEntity="TestCustomIdentifier")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parent;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="TestCustomIdentifier")
     * @ORM\JoinTable(name="test_api_custom_id_children",
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
     * @return TestCustomIdentifier|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param TestCustomIdentifier $item
     */
    public function setParent($item)
    {
        $this->parent = $item;
    }

    /**
     * @return Collection|TestCustomIdentifier[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param TestCustomIdentifier $item
     */
    public function addChild(TestCustomIdentifier $item)
    {
        $this->children->add($item);
    }

    /**
     * @param TestCustomIdentifier $item
     */
    public function removeChild(TestCustomIdentifier $item)
    {
        $this->children->removeElement($item);
    }
}
