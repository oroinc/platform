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
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="manager_id", referencedColumnName="id")
     **/
    protected $manager;

    /**
     * @ORM\OneToMany(targetEntity="Person", mappedBy="department")
     */
    protected $staff;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="products")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id")
     **/
    protected $owner;

    public function __construct()
    {
        $this->staff = new ArrayCollection();
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
     * @return Person
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param Person|null $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return Collection|Person[]
     */
    public function getStaff()
    {
        return $this->staff;
    }

    public function setStaff(Collection $staff)
    {
        $this->staff = $staff;
    }

    public function addStaff(Person $person)
    {
        if (!$this->staff->contains($person)) {
            $this->staff->add($person);
            $person->setDepartment($this);
        }
    }

    public function removeStaff(Person $person)
    {
        if ($this->staff->contains($person)) {
            $this->staff->removeElement($person);
            $person->setDepartment(null);
        }
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param User|null $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }
}
