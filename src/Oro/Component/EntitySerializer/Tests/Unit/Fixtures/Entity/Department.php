<?php

namespace Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="department_table")
 */
class Department
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private ?string $name = null;

    /**
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="manager_id", referencedColumnName="id")
     */
    private ?Person $manager = null;

    /**
     * @ORM\OneToMany(targetEntity="Person", mappedBy="department")
     */
    private Collection $staff;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="products")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id")
     */
    private ?User $owner = null;

    public function __construct()
    {
        $this->staff = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getManager(): ?Person
    {
        return $this->manager;
    }

    public function setManager(?Person $manager): void
    {
        $this->manager = $manager;
    }

    /**
     * @return Collection<Person>
     */
    public function getStaff(): Collection
    {
        return $this->staff;
    }

    /**
     * @param Collection<Person> $staff
     */
    public function setStaff(Collection $staff): void
    {
        $this->staff = $staff;
    }

    public function addStaff(Person $person): void
    {
        if (!$this->staff->contains($person)) {
            $this->staff->add($person);
            $person->setDepartment($this);
        }
    }

    public function removeStaff(Person $person): void
    {
        if ($this->staff->contains($person)) {
            $this->staff->removeElement($person);
            $person->setDepartment(null);
        }
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): void
    {
        $this->owner = $owner;
    }
}
