<?php

namespace Oro\Bundle\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table(name="oro_entity_fallback_value")
 * @ORM\Entity()
 * @Config()
 */
class EntityFieldFallbackValue
{
    const FALLBACK_TYPE = 'fallbackType';
    const FALLBACK_LIST_KEY = 'fallbackList';

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
     * @var mixed
     *
     * @ORM\Column(name="scalar_value", type="string", length=255, nullable=true)
     */
    protected $scalarValue;

    /**
     * @var array
     *
     * @ORM\Column(name="array_value", type="array", nullable=true)
     */
    protected $arrayValue;

    /**
     * @var mixed
     */
    protected $viewValue;

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
     * @return $this
     */
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;

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
     * @return $this
     */
    public function setUseFallback($useFallback)
    {
        $this->useFallback = $useFallback;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getScalarValue()
    {
        return $this->scalarValue;
    }

    /**
     * @param mixed $scalarValue
     * @return $this
     */
    public function setScalarValue($scalarValue)
    {
        $this->scalarValue = $scalarValue;

        return $this;
    }

    /**
     * @return array
     */
    public function getArrayValue()
    {
        return $this->arrayValue;
    }

    /**
     * @param array $arrayValue
     * @return $this
     */
    public function setArrayValue($arrayValue)
    {
        $this->arrayValue = $arrayValue;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getViewValue()
    {
        return $this->viewValue;
    }

    /**
     * @param mixed $viewValue
     * @return $this
     */
    public function setViewValue($viewValue)
    {
        $this->viewValue = $viewValue;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOwnValue()
    {
        if (!is_null($this->scalarValue)) {
            return $this->scalarValue;
        }

        return $this->arrayValue;
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        return (string)$this->scalarValue;
    }
}
