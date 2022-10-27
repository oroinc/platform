<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ParameterBag;
use Oro\Component\ChainProcessor\ToArrayInterface;

/**
 * The base class for classes represents metadata for different kind of entity properties.
 */
abstract class PropertyMetadata extends ParameterBag implements ToArrayInterface
{
    private const MASK_DIRECTION_INPUT = 1;
    private const MASK_DIRECTION_OUTPUT = 2;
    private const MASK_DIRECTION_BIDIRECTIONAL = 3;

    private ?string $name;
    private ?string $propertyPath = null;
    private ?string $dataType = null;
    private int $flags;

    public function __construct(string $name = null)
    {
        $this->name = $name;
        $this->flags = self::MASK_DIRECTION_BIDIRECTIONAL;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
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
        if ($this->isHidden()) {
            $result['hidden'] = true;
        }

        return $result;
    }

    /**
     * Gets the name of a property.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets the name of a property.
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Gets the name of a property in the source entity.
     * Returns NULL if the property is not mapped.
     */
    public function getPropertyPath(): ?string
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
    public function setPropertyPath(?string $propertyPath): void
    {
        $this->propertyPath = $propertyPath;
    }

    /**
     * Gets the data-type of a property.
     */
    public function getDataType(): ?string
    {
        return $this->dataType;
    }

    /**
     * Sets the data-type of a property.
     */
    public function setDataType(?string $dataType): void
    {
        $this->dataType = $dataType;
    }

    /**
     * Indicates whether the request data can contain this property.
     */
    public function isInput(): bool
    {
        return $this->hasFlag(self::MASK_DIRECTION_INPUT);
    }

    /**
     * Indicates whether the response data can contain this property.
     */
    public function isOutput(): bool
    {
        return $this->hasFlag(self::MASK_DIRECTION_OUTPUT);
    }

    /**
     * Sets a value indicates whether the request data and the response data can contain this property.
     */
    public function setDirection(bool $input, bool $output): void
    {
        $this->setFlag($input, self::MASK_DIRECTION_INPUT);
        $this->setFlag($output, self::MASK_DIRECTION_OUTPUT);
    }

    /**
     * Indicates whether the request data and response data cannot contain this property.
     */
    public function isHidden(): bool
    {
        return !$this->isOutput() && !$this->isInput();
    }

    /**
     * Sets a flag indicates that the request data and response data cannot contain this property.
     */
    public function setHidden(): void
    {
        $this->setDirection(false, false);
    }

    protected function hasFlag(int $valueMask): bool
    {
        return $valueMask === ($this->flags & $valueMask);
    }

    protected function setFlag(bool $value, int $valueMask): void
    {
        if ($value) {
            $this->flags |= $valueMask;
        } else {
            $this->flags &= ~$valueMask;
        }
    }
}
