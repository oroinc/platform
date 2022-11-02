<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

/**
 * The interface for security metadata classes.
 */
interface ClassSecurityMetadata
{
    /**
     * Gets the class name.
     *
     * @return string
     */
    public function getClassName();

    /**
     * Gets the security group name.
     *
     * @return string
     */
    public function getGroup();

    /**
     * Gets the label.
     *
     * @return string|Label|null
     */
    public function getLabel();

    /**
     * Gets the description.
     *
     * @return string|Label|null
     */
    public function getDescription();

    /**
     * Gets the category.
     *
     * @return string
     */
    public function getCategory();

    /**
     * Gets the fields array if this class supports fields.
     *
     * @return FieldSecurityMetadata[] [field name => FieldSecurityMetadata, ...]
     */
    public function getFields();
}
