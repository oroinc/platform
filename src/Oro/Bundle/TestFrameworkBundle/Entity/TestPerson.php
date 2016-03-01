<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="test_person")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "employee" = "Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee"
 * })
 */
abstract class TestPerson implements TestFrameworkEntityInterface
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
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\TestFrameworkBundle\Entity\TestDepartment",
     *     inversedBy="staff"
     * )
     * @ORM\JoinColumn(name="department_id", referencedColumnName="id"),
     */
    protected $department;

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
     * @return TestDepartment|null
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * @param TestDepartment|null $department
     */
    public function setDepartment($department)
    {
        $this->department = $department;
    }
}
