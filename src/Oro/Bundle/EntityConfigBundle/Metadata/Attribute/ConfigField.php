<?php

namespace Oro\Bundle\EntityConfigBundle\Metadata\Attribute;

use Attribute;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Exception\AttributeException;

/**
 * The attribute that is used to provide configuration for fields of configurable entity.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ConfigField
{
    private const MODES = [ConfigModel::MODE_DEFAULT, ConfigModel::MODE_HIDDEN, ConfigModel::MODE_READONLY];

    private const DEFINED_PROPERTIES_LIST = [
        'mode',
        'defaultValues',
    ];

    public string $mode = ConfigModel::MODE_DEFAULT;
    public array $defaultValues = [];

    public function __construct(...$optionsList)
    {
        foreach ($optionsList as $optionName => $optionValue) {
            if (is_int($optionName) && is_array($optionValue)) {
                throw new AttributeException(sprintf(
                    'Attribute "ConfigField" does not support array as an argument. Use named arguments instead.'
                ));
            }

            if (in_array($optionName, self::DEFINED_PROPERTIES_LIST)) {
                $this->{$optionName} = $optionValue;
            } else {
                if ('value' === $optionName) {
                    $this->mode = $optionValue;
                    continue;
                }

                throw new AttributeException(sprintf(
                    'Attribute "ConfigField" does not support argument : "%s"',
                    $optionName
                ));
            }
        }

        if (!\in_array($this->mode, self::MODES, true)) {
            throw new AttributeException(sprintf(
                'Attribute "ConfigField" has an invalid value parameter "mode" : "%s"',
                $this->mode
            ));
        }
    }
}
