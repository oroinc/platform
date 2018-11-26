<?php

namespace Oro\Bundle\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * The entity the represents a field that value can be a scalar, an array
 * or it can retrieved from another source if it does not have own value.
 * @ORM\Table(name="oro_entity_fallback_value")
 * @ORM\Entity()
 * @Config()
 */
class EntityFieldFallbackValue
{
    const FALLBACK_TYPE = 'fallbackType';
    const FALLBACK_LIST = 'fallbackList';
    const FALLBACK_SCALAR_FIELD = 'scalarValue';
    const FALLBACK_ARRAY_FIELD = 'arrayValue';
    const FALLBACK_PARENT_FIELD = 'fallback';

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
    public function getOwnValue()
    {
        if (!is_null($this->scalarValue)) {
            return $this->scalarValue;
        }

        return $this->arrayValue;
    }

    public function __clone()
    {
        $this->id = null;
    }
}
