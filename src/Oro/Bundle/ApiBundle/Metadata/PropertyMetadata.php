<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ParameterBag;

/**
 * The base class for classes represents metadata for different kind of entity properties.
 */
abstract class PropertyMetadata extends ParameterBag
{
    private const MASK_DIRECTION_INPUT         = 1;
    private const MASK_DIRECTION_OUTPUT        = 2;
    private const MASK_DIRECTION_BIDIRECTIONAL = 3;

    /** @var string */
    private $name;

    /** @var string */
    private $propertyPath;

    /** @var string */
    private $dataType;

    /** @var integer */
    private $flags;

    /**
     * PropertyMetadata constructor.
     *
     * @param string|null $name
     */
    public function __construct($name = null)
    {
        $this->name = $name;
        $this->flags = self::MASK_DIRECTION_BIDIRECTIONAL;
    }

    /**
     * Gets a native PHP array representation of the object.
     *
     * @return array [key => value, ...]
     */
    public function toArray()
    {
        $result = ['name' => $this->name];
        if ($this->propertyPath) {
            $result['property_path'] = $this->propertyPath;
        }
        if ($this->dataType) {
            $result['data_type'] = $this->dataType;
        }
        if ($this->isInput() && !$this->isOutput()) {
            $result['direction'] = 'input-only';
        } elseif ($this->isOutput() && !$this->isInput()) {
            $result['direction'] = 'output-only';
        }

        return $result;
    }

    /**
     * Gets the name of a property.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of a property.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the name of a property in the source entity.
     *
     * @return string The property path or NULL if the property is not mapped.
     */
    public function getPropertyPath()
    {
        if (null === $this->propertyPath) {
            return $this->name;
        }

        return ConfigUtil::IGNORE_PROPERTY_PATH !== $this->propertyPath
            ? $this->propertyPath
            : null;
    }

    /**
     * Sets the name of a property in the source entity.
     *
     * @param string|null $propertyPath The property path,
     *                                  NULL if the property path equals to name
     *                                  or "_" (ConfigUtil::IGNORE_PROPERTY_PATH) if the property is not mapped.
     */
    public function setPropertyPath($propertyPath)
    {
        $this->propertyPath = $propertyPath;
    }

    /**
     * Gets the data-type of a property.
     *
     * @return string
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * Sets the data-type of a property.
     *
     * @param string $dataType
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
    }

    /**
     * Indicates whether the request data can contain this property.
     *
     * @return bool
     */
    public function isInput()
    {
        return $this->hasFlag(self::MASK_DIRECTION_INPUT);
    }

    /**
     * Indicates whether the response data can contain this property.
     *
     * @return bool
     */
    public function isOutput()
    {
        return $this->hasFlag(self::MASK_DIRECTION_OUTPUT);
    }

    /**
     * Sets a value indicates whether the request data and the response data can contain this property.
     *
     * @param bool $input
     * @param bool $output
     */
    public function setDirection($input, $output)
    {
        $this->setFlag($input, self::MASK_DIRECTION_INPUT);
        $this->setFlag($output, self::MASK_DIRECTION_OUTPUT);
    }

    /**
     * @param int $valueMask
     *
     * @return bool
     */
    protected function hasFlag($valueMask)
    {
        return $valueMask === ($this->flags & $valueMask);
    }

    /**
     * @param bool $value
     * @param int  $valueMask
     */
    protected function setFlag($value, $valueMask)
    {
        if ($value) {
            $this->flags |= $valueMask;
        } else {
            $this->flags &= ~$valueMask;
        }
    }
}
