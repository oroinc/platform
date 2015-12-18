<?php

namespace Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="group_table")
 */
class Group
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=50)
     */
    protected $name;

    /**
     * @ORM\Column(name="label", type="string", length=255, nullable=true)
     */
    protected $label;

    /**
     * @ORM\Column(name="public", type="boolean")
     */
    protected $public = false;

    /**
     * This field has getter and setter which not match the field name
     * and it is used to test that such fields are serialized using direct property access
     *
     * @ORM\Column(name="is_exception", type="boolean")
     */
    protected $isException;

    /**
     * @param int|null $id
     */
    public function __construct($id = null)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
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
     *
     * @return self
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * @param boolean $public
     *
     * @return self
     */
    public function setPublic($public)
    {
        $this->public = $public;

        return $this;
    }

    /**
     * @return bool
     */
    public function isException()
    {
        return $this->isException;
    }

    /**
     * @param boolean $exception
     *
     * @return self
     */
    public function setException($exception)
    {
        $this->isException = $exception;

        return $this;
    }

    /**
     * @return string
     */
    public function getComputedName()
    {
        return sprintf('%s (COMPUTED)', $this->name);
    }
}
