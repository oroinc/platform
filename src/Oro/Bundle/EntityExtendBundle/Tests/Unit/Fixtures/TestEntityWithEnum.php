<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="test_table")
 * @ORM\Entity()
 */
class TestEntityWithEnum
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var TestEnumValue|null
     *
     * @ORM\ManyToOne(targetEntity="TestEnumValue")
     * @ORM\JoinColumn(name="singleEnumField_id", referencedColumnName="code")
     */
    private $singleEnumField;

    /**
     * @var Collection|TestEnumValue[]
     *
     * @ORM\ManyToMany(targetEntity="TestEnumValue")
     * @ORM\JoinTable(name="oro_ref_enum_test",
     *      joinColumns={
     *          @ORM\JoinColumn(name="multipleEnumField_id", referencedColumnName="code")
     *      }
     * )
     */
    private $multipleEnumField;

    /** @var string */
    private $multipleEnumFieldSnapshot;

    public function __construct()
    {
        $this->multipleEnumField = new ArrayCollection();
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
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return TestEnumValue|null
     */
    public function getSingleEnumField()
    {
        return $this->singleEnumField;
    }

    /**
     * @param TestEnumValue|null $value
     */
    public function setSingleEnumField($value)
    {
        $this->singleEnumField = $value;
    }

    /**
     * @return Collection|TestEnumValue[]
     */
    public function getMultipleEnumField()
    {
        return $this->multipleEnumField;
    }

    /**
     * @param TestEnumValue $value
     */
    public function addMultipleEnumField($value)
    {
        $this->multipleEnumField->add($value);
    }

    /**
     * @param TestEnumValue $value
     */
    public function removeMultipleEnumField($value)
    {
        $this->multipleEnumField->removeElement($value);
    }

    /**
     * @return string|null
     */
    public function getMultipleEnumFieldSnapshot()
    {
        return $this->multipleEnumFieldSnapshot;
    }

    /**
     * @param string|null $value
     *
     * @return $this
     */
    public function setMultipleEnumFieldSnapshot($value)
    {
        $this->multipleEnumFieldSnapshot = $value;

        return $this;
    }
}
