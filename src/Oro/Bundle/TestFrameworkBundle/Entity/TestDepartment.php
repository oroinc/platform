<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="test_department")
 */
class TestDepartment implements TestFrameworkEntityInterface
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var TestDepartment|null
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\TestFrameworkBundle\Entity\TestPerson",
     *     mappedBy="department"
     * )
     */
    protected $staff;

    public function __construct()
    {
        $this->staff  = new ArrayCollection();
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
     * @return Collection|TestPerson[]
     */
    public function getStaff()
    {
        return $this->staff;
    }

    /**
     * @param Collection $staff
     *
     * @return self
     */
    public function setStaff(Collection $staff)
    {
        $this->staff = $staff;

        return $this;
    }

    /**
     * @param TestPerson $person
     *
     * @return self
     */
    public function addStaff(TestPerson $person)
    {
        if (!$this->staff->contains($person)) {
            $this->staff->add($person);
        }

        return $this;
    }

    /**
     * @param TestPerson $person
     *
     * @return self
     */
    public function removeStaff(TestPerson $person)
    {
        if ($this->staff->contains($person)) {
            $this->staff->removeElement($person);
        }

        return $this;
    }
}
