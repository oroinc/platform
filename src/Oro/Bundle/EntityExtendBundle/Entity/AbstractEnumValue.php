<?php

namespace Oro\Bundle\EntityExtendBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Oro\Bundle\FormBundle\Entity\PriorityItem;

/**
 * The base class for all entities represent values for a particular enum
 * @deprecated
 */
#[ORM\MappedSuperclass]
abstract class AbstractEnumValue implements Translatable, PriorityItem
{
    #[ORM\Column(name: 'id', type: Types::STRING, length: 32)]
    #[ORM\Id]
    private ?string $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    #[Gedmo\Translatable]
    private ?string $name = null;

    #[ORM\Column(name: 'priority', type: Types::INTEGER)]
    private ?int $priority = null;

    #[ORM\Column(name: 'is_default', type: Types::BOOLEAN)]
    private ?bool $default = null;

    #[Gedmo\Locale]
    protected ?string $locale = null;

    /**
     * @param string  $id
     * @param string  $name
     * @param int     $priority
     * @param boolean $default
     */
    public function __construct($id, $name, $priority = 0, $default = false)
    {
        $this->id       = $id;
        $this->name     = $name;
        $this->priority = (int)$priority;
        $this->default  = (bool)$default;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return AbstractEnumValue
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
     * @param int $priority
     *
     * @return AbstractEnumValue
     */
    public function setPriority($priority)
    {
        $this->priority = (int)$priority;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param boolean $default
     *
     * @return AbstractEnumValue
     */
    public function setDefault($default)
    {
        $this->default = (bool)$default;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * @param string $locale
     *
     * @return AbstractEnumValue
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Get a human-readable representation of this object.
     * This method is used for rendering on UI as well
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->name;
    }
}
