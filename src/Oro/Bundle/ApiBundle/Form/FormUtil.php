<?php

namespace Oro\Bundle\ApiBundle\Form;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\PropertyMetadata;

class FormUtil
{
    const EXTRA_FIELDS_MESSAGE = 'oro.api.form.extra_fields';

    /**
     * Returns default options of a form.
     *
     * @return array
     */
    public static function getFormDefaultOptions()
    {
        return [
            'validation_groups'    => ['Default', 'api'],
            'extra_fields_message' => self::EXTRA_FIELDS_MESSAGE
        ];
    }

    /**
     * Gets options of a form field.
     *
     * @param PropertyMetadata            $property
     * @param EntityDefinitionFieldConfig $config
     *
     * @return array
     */
    public static function getFormFieldOptions(PropertyMetadata $property, EntityDefinitionFieldConfig $config)
    {
        $options = $config->getFormOptions();
        if (null === $options) {
            $options = [];
        }
        $propertyPath = $property->getPropertyPath();
        if (!$propertyPath) {
            $options['mapped'] = false;
        } elseif ($propertyPath !== $property->getName()) {
            $options['property_path'] = $propertyPath;
        }

        return $options;
    }
}
