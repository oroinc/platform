<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="category_table")
 */
class Category
{
    /**
     * @ORM\Column(name="name", type="string", length=50)
     * @ORM\Id
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255, unique=true)
     */
    protected $label;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
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
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->name;
    }
}
