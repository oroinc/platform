<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\Enum\EnumField;

/**
 * @ORM\Entity()
 * @ORM\Table()
 */
class TestEntityWithEnumField
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", enumType=EnumField::class)
     */
    protected EnumField $enum = EnumField::Option1;

    public function __construct($id = null)
    {
        $this->id = $id;
    }

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
     * @return EnumField
     */
    public function getType(): EnumField
    {
        return $this->enum;
    }

    /**
     * @param EnumField $enum
     *
     * @return void
     */
    public function setType(EnumField $enum)
    {
        $this->enum = $enum->value;
    }
}
