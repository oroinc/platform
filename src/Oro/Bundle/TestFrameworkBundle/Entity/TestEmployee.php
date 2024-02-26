<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Test Employee
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'test_employee')]
class TestEmployee implements TestFrameworkEntityInterface
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    protected ?string $name = null;

    #[ORM\ManyToOne(targetEntity: TestDepartment::class, inversedBy: 'employees')]
    #[ORM\JoinColumn(name: 'department_id', referencedColumnName: 'id')]
    protected ?TestDepartment $department = null;

    #[ORM\Column(name: 'position', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $position = null;

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

    /**
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param string $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }
}
