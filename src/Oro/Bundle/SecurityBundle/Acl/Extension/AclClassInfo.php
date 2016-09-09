<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;

interface AclClassInfo
{
    /**
     * Gets the class name
     *
     * @return string
     */
    public function getClassName();

    /**
     * Gets the security group name
     *
     * @return string
     */
    public function getGroup();

    /**
     * Gets a label
     *
     * @return string
     */
    public function getLabel();

    /**
     * Gets the description
     *
     * @return string
     */
    public function getDescription();

    /**
     * Gets the category
     *
     * @return string
     */
    public function getCategory();

    /**
     * Gets the fields array in case if given class supports fields
     *
     * @return array|FieldSecurityMetadata[]
     */
    public function getFields();
}
