<?php

namespace Oro\Bundle\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_entity_field_fallback_val")
 * @ORM\Entity()
 */
class EntityFieldFallbackValue
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="fallback", type="string", length=64, nullable=true)
     */
    protected $fallback;

    /**
     * @var string
     *
     * @ORM\Column(name="string_value", type="string", length=64, nullable=true)
     */
    protected $stringValue;

    /**
     * @var bool
     */
    protected $useFallback;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFallback()
    {
        return $this->fallback;
    }

    /**
     * @param string $fallback
     *
     * @return $this
     */
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;

        return $this;
    }

    /**
     * @return string
     */
    public function getStringValue()
    {
        return $this->stringValue;
    }

    /**
     * @param string $stringValue
     *
     * @return $this
     */
    public function setStringValue($stringValue)
    {
        $this->stringValue = $stringValue;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isUseFallback()
    {
        return $this->useFallback;
    }

    /**
     * @param boolean $useFallback
     *
     * @return $this
     */
    public function setUseFallback($useFallback)
    {
        $this->useFallback = $useFallback;

        return $this;
    }

    public function __toString()
    {
        return $this->stringValue;
    }

}
