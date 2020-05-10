<?php

namespace Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="person_table")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "employee" = "Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Employee",
 *     "buyer" = "Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Buyer"
 * })
 */
abstract class Person
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="Department", inversedBy="staff")
     * @ORM\JoinColumn(name="department_id", referencedColumnName="id"),
     */
    protected $department;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="products")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id")
     **/
    protected $owner;

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
     * @return Department|null
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * @param Department|null $department
     */
    public function setDepartment($department)
    {
        $this->department = $department;
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
