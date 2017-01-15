<?php

namespace Oro\Bundle\ApiBundle\Config;

interface FieldConfigInterface extends ConfigBagInterface
{
    /**
     * Indicates whether the exclusion flag is set explicitly.
     *
     * @return bool
     */
    public function hasExcluded();

    /**
     * Indicates whether the field should be excluded.
     *
     * @return bool
     */
    public function isExcluded();

    /**
     * Sets a flag indicates whether the field should be excluded.
     *
     * @param bool $exclude
     */
    public function setExcluded($exclude = true);

    /**
     * Indicates whether the path of the field value exists.
     *
     * @return string
     */
    public function hasPropertyPath();

    /**
     * Gets the path of the field value.
     *
     * @param string|null $defaultValue
     *
     * @return string|null
     */
    public function getPropertyPath($defaultValue = null);

    /**
     * Sets the path of the field value.
     *
     * @param string|null $propertyPath
     */
    public function setPropertyPath($propertyPath = null);
}
