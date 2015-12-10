<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetFieldConfig;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

class FieldConfigContext extends ConfigContext
{
    /** the name of a field */
    const FIELD_NAME = 'field';

    /**
     * Gets the name of a field.
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->get(self::FIELD_NAME);
    }

    /**
     * Sets the name of a field.
     *
     * @param string $fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->set(self::FIELD_NAME, $fieldName);
    }
}
