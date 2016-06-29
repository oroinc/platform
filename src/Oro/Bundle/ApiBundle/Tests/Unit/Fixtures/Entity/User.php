<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_table")
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=50)
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="category_name", referencedColumnName="name", nullable=false)
     **/
    protected $category;

    /**
     * @ORM\ManyToMany(targetEntity="Group")
     * @ORM\JoinTable(name="user_to_group_table",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_group_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $groups;

    /**
     * @ORM\OneToMany(targetEntity="Product", mappedBy="owner")
     */
    protected $products;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", nullable=false)
     **/
    protected $owner;

    public function __construct()
    {
        $this->groups   = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return Group[]|Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param Group[]|Collection $groups
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;
    }

    /**
     * @param Group $group
     */
    public function addGroup(Group $group)
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
        }
    }

    /**
     * @param Group $group
     */
    public function removeGroup(Group $group)
    {
        if ($this->groups->contains($group)) {
            $this->groups->removeElement($group);
        }
    }

    /**
     * @return Product[]|Collection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @param Product $product
     */
    public function addProduct(Product $product)
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setOwner($this);
        }
    }

    /**
     * @param Product $product
     */
    public function removeProduct(Product $product)
    {
        if ($this->products->contains($product)) {
            $this->products->removeElement($product);
            $product->setOwner(null);
        }
    }

    /**
     * @return User|null
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param User|null $owner
     */
    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->name;
    }
}
