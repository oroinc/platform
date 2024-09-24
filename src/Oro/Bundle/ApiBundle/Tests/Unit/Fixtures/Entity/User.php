<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_table')]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 50)]
    protected ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_name', referencedColumnName: 'name', nullable: false)]
    protected ?Category $category = null;

    /**
     * @var Collection<int, Group>
     */
    #[ORM\ManyToMany(targetEntity: Group::class)]
    #[ORM\JoinTable(name: 'user_to_group_table')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'user_group_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $groups = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Product::class)]
    protected ?Collection $products = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: false)]
    protected ?User $owner = null;

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
    #[\Override]
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

    public function addGroup(Group $group)
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
        }
    }

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

    public function addProduct(Product $product)
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setOwner($this);
        }
    }

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

    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->name;
    }

    /**
     * Getter for extended manyToMany association
     *
     * @return array
     */
    public function getTargets()
    {
        $targets = [];
        if ($this->products->count()) {
            $targets = array_merge($targets, $this->products->toArray());
        }
        if ($this->groups->count()) {
            $targets = array_merge($targets, $this->groups->toArray());
        }

        return $targets;
    }

    /**
     * Adder for extended manyToMany association
     */
    public function addTarget($target)
    {
        if ($target instanceof Product && !$this->products->contains($target)) {
            $this->products->add($target);
        }
        if ($target instanceof Group && !$this->groups->contains($target)) {
            $this->groups->add($target);
        }
    }

    /**
     * Remover for extended manyToMany association
     */
    public function removeTarget($target)
    {
        if ($target instanceof Product && $this->products->contains($target)) {
            $this->products->removeElement($target);
        }
        if ($target instanceof Group && $this->groups->contains($target)) {
            $this->groups->removeElement($target);
        }
    }
}
