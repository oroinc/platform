<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\Enum\EnumField;

/**
 * @ORM\Entity()
 * @ORM\Table()
 */
class TestEntityWithMagicEnumField
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

    public function __isset($name)
    {
        return 'enum' === $name;
    }

    public function __get($name)
    {
        if ('enum' !== $name) {
            throw new \Error(sprintf('The property "%s" does not exist or no access to it.', $name));
        }

        return $this->{$name};
    }

    public function __set($name, $value)
    {
        if ('enum' !== $name) {
            throw new \Error(sprintf('The property "%s" does not exist or no access to it.', $name));
        }

        $this->enum = $value;
    }
}
