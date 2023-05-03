<?php

namespace Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="product_table")
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id;

    /**
     * @ORM\Column(name="name", type="string", length=50)
     */
    private ?string $name = null;

    /**
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="category_name", referencedColumnName="name")
     */
    private ?Category $category = null;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="products")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id")
     */
    private ?User $owner = null;

    /**
     * @ORM\ManyToMany(targetEntity="Group")
     * @ORM\JoinTable(name="rel_product_to_group_table",
     *      joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="product_group_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    private Collection $groups;

    public function __construct(int $id = null)
    {
        $this->id = $id;
        $this->groups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<Group>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Group $group): static
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
        }

        return $this;
    }

    public function removeGroup(Group $group): static
    {
        if ($this->groups->contains($group)) {
            $this->groups->removeElement($group);
        }

        return $this;
    }
}
