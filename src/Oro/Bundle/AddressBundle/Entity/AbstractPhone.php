<?php

namespace Oro\Bundle\AddressBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\FormBundle\Entity\EmptyItem;
use Oro\Bundle\FormBundle\Entity\PrimaryItem;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractPhone implements PrimaryItem, EmptyItem
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=255, nullable=false)
     * @Soap\ComplexType("string", nillable=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          },
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $phone;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_primary", type="boolean", nullable=true)
     * @Soap\ComplexType("boolean", nillable=true)
     */
    protected $primary;

    /**
     * @param string|null $phone
     */
    public function __construct($phone = null)
    {
        $this->phone = $phone;
        $this->primary = false;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return AbstractPhone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param bool $primary
     * @return AbstractPhone
     */
    public function setPrimary($primary)
    {
        $this->primary = (bool)$primary;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPrimary()
    {
        return $this->primary;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getPhone();
    }

    /**
     * Check if entity is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->phone);
    }

    /**
     * @param mixed $other
     * @return bool
     */
    public function isEqual($other)
    {
        $class = ClassUtils::getClass($this);

        /** @var AbstractAddress $other */
        if (!$other instanceof $class) {
            return false;
        } elseif ($this->getId() && $other->getId()) {
            return $this->getId() == $other->getId();
        } elseif ($this->getId() || $other->getId()) {
            return false;
        } else {
            return $this == $other;
        }
    }
}
