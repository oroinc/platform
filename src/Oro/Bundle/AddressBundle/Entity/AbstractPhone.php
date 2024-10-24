<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\FormBundle\Entity\EmptyItem;
use Oro\Bundle\FormBundle\Entity\PrimaryItem;

/**
* AbstractPhone abstract class
*
*/
#[ORM\MappedSuperclass]
abstract class AbstractPhone implements PrimaryItem, EmptyItem
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'phone', type: Types::STRING, length: 255, nullable: false)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true], 'dataaudit' => ['auditable' => true]])]
    protected ?string $phone = null;

    #[ORM\Column(name: 'is_primary', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $primary = null;

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
    #[\Override]
    public function setPrimary($primary)
    {
        $this->primary = (bool)$primary;

        return $this;
    }

    /**
     * @return bool
     */
    #[\Override]
    public function isPrimary()
    {
        return $this->primary;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getPhone();
    }

    /**
     * Check if entity is empty.
     *
     * @return bool
     */
    #[\Override]
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
