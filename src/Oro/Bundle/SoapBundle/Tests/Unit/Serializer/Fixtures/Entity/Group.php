<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Serializer\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="oro_test_serializer_group")
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
     * This field has no getter and setter and it is used to test that such fields are not serialized
     *
     * @ORM\Column(name="hidden", type="boolean")
     */
    protected $hidden;

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
     * @return string
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
}
