<?php

namespace Oro\Bundle\BusinessEntitiesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class BaseCustomerGroupEntity
 *
 * @package Oro\Bundle\BusinessEntitiesBundle\Entity
 *
 * @ORM\MappedSuperclass
 */
class BaseCustomerGroupEntity
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Customer groups doesn't have code,
     * but it's for compatibility with CustomerNormalizer
     *
     * @param $code
     * @return $this
     */
    public function setCode($code)
    {
        return $this;
    }
}
