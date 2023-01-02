<?php

namespace Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="role_table")
 */
class Role
{
    /**
     * @ORM\Column(name="code", type="string", length=50)
     * @ORM\Id
     */
    private ?string $code;

    /**
     * @ORM\Column(name="label", type="string", length=255, unique=true)
     */
    private ?string $label = null;

    /**
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="category_name", referencedColumnName="name")
     */
    private ?Category $category = null;

    /**
     * @ORM\ManyToMany(targetEntity="Group")
     * @ORM\JoinTable(name="rel_role_to_group_table",
     *      joinColumns={@ORM\JoinColumn(name="role_code", referencedColumnName="code", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="role_group_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    private Collection $groups;

    public function __construct(string $code = null)
    {
        $this->code = $code;
        $this->groups = new ArrayCollection();
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

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
