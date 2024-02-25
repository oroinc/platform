<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Test Department
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'test_department')]
class TestDepartment implements TestFrameworkEntityInterface
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    protected ?string $name = null;

    /**
     * @var Collection<int, TestEmployee>
     */
    #[ORM\OneToMany(mappedBy: 'department', targetEntity: TestEmployee::class)]
    protected ?Collection $employees = null;

    public function __construct()
    {
        $this->employees  = new ArrayCollection();
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
     * @return Collection|TestEmployee[]
     */
    public function getEmployees()
    {
        return $this->employees;
    }

    /**
     * @param Collection $employees
     *
     * @return self
     */
    public function setEmployees(Collection $employees)
    {
        $this->employees = $employees;

        return $this;
    }

    /**
     * @param TestEmployee $person
     *
     * @return self
     */
    public function addEmployee(TestEmployee $person)
    {
        if (!$this->employees->contains($person)) {
            $this->employees->add($person);
        }

        return $this;
    }

    /**
     * @param TestEmployee $person
     *
     * @return self
     */
    public function removeEmployee(TestEmployee $person)
    {
        if ($this->employees->contains($person)) {
            $this->employees->removeElement($person);
        }

        return $this;
    }
}
