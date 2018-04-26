<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;

/**
 * The trait for "direction" option related methods.
 * Using this option it is able to specify whether the request only,
 * the response only or both can contain a field.
 *
 * @property array $items
 */
trait DirectionTrait
{
    /**
     * Indicates whether the direction option is set explicitly.
     * If this option is not set, both the request and the response can contain this field.
     *
     * @return bool
     */
    public function hasDirection()
    {
        return array_key_exists(EntityDefinitionFieldConfig::DIRECTION, $this->items);
    }

    /**
     * Sets a value that indicates whether the field is input-only, output-only or bidirectional.
     *
     * * The "input-only" means that the request data can contain this field,
     *   but the response data cannot.
     * * The "output-only" means that the response data can contain this field,
     *   but the request data cannot.
     * * The "bidirectional" means that both the request data and the response data can contain this field.
     *
     * The "bidirectional" is the default behaviour.
     *
     * @param string|null $direction Can be "input-only", "output-only", "bidirectional"
     *                               or NULL to remove this option and use default behaviour for it
     */
    public function setDirection($direction)
    {
        if ($direction) {
            if (EntityDefinitionFieldConfig::DIRECTION_INPUT_ONLY !== $direction
                && EntityDefinitionFieldConfig::DIRECTION_OUTPUT_ONLY !== $direction
                && EntityDefinitionFieldConfig::DIRECTION_BIDIRECTIONAL !== $direction
            ) {
                throw new \InvalidArgumentException(sprintf(
                    'The possible values for the direction are "%s", "%s" or "%s".',
                    EntityDefinitionFieldConfig::DIRECTION_INPUT_ONLY,
                    EntityDefinitionFieldConfig::DIRECTION_OUTPUT_ONLY,
                    EntityDefinitionFieldConfig::DIRECTION_BIDIRECTIONAL
                ));
            }
            $this->items[EntityDefinitionFieldConfig::DIRECTION] = $direction;
        } else {
            unset($this->items[EntityDefinitionFieldConfig::DIRECTION]);
        }
    }

    /**
     * Indicates whetner the request data can contain this field.
     *
     * @return bool
     */
    public function isInput()
    {
        if (!array_key_exists(EntityDefinitionFieldConfig::DIRECTION, $this->items)) {
            return true;
        }

        $direction = $this->items[EntityDefinitionFieldConfig::DIRECTION];

        return
            EntityDefinitionFieldConfig::DIRECTION_INPUT_ONLY === $direction
            || EntityDefinitionFieldConfig::DIRECTION_BIDIRECTIONAL === $direction;
    }

    /**
     * Indicates whetner the response data can contain this field.
     *
     * @return bool
     */
    public function isOutput()
    {
        if (!array_key_exists(EntityDefinitionFieldConfig::DIRECTION, $this->items)) {
            return true;
        }

        $direction = $this->items[EntityDefinitionFieldConfig::DIRECTION];

        return
            EntityDefinitionFieldConfig::DIRECTION_OUTPUT_ONLY === $direction
            || EntityDefinitionFieldConfig::DIRECTION_BIDIRECTIONAL === $direction;
    }
}
