<?php

namespace Oro\Bundle\EntityConfigBundle\Metadata\Attribute;

use Attribute;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Exception\AttributeException;

/**
 * The attribute that is used to provide configuration of configurable entity.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Config
{
    private const MODES = [ConfigModel::MODE_DEFAULT, ConfigModel::MODE_HIDDEN, ConfigModel::MODE_READONLY];

    private const DEFINED_PROPERTIES_LIST = [
        'mode',
        'routeName',
        'routeView',
        'routeCreate',
        'defaultValues',
    ];

    public array $routes = [];
    public string $routeName = '';
    public string $routeView = '';
    public string $routeCreate = '';
    public array $defaultValues = [];
    public string $mode = ConfigModel::MODE_DEFAULT;

    public function __construct(...$optionsList)
    {
        foreach ($optionsList as $optionName => $optionValue) {
            if (is_int($optionName) && is_array($optionValue)) {
                throw new AttributeException(sprintf(
                    'Attribute "Config" does not support array as an argument. Use named arguments instead.'
                ));
            }

            if (in_array($optionName, self::DEFINED_PROPERTIES_LIST)) {
                $this->{$optionName} = $optionValue;
            } elseif (!str_starts_with($optionName, 'route')) {
                if ('value' === $optionName) {
                    $this->mode = $optionValue;
                    continue;
                }

                throw new AttributeException(sprintf(
                    'Attribute "Config" does not support argument : "%s"',
                    $optionName
                ));
            }
        }

        if (!\in_array($this->mode, self::MODES, true)) {
            throw new AttributeException(sprintf(
                'Attribute "Config" has an invalid value parameter "mode" : "%s"',
                $this->mode
            ));
        }

        $this->collectRoutes($optionsList);
    }

    private function collectRoutes(array $data): void
    {
        foreach ($data as $name => $value) {
            if (str_starts_with($name, 'route') && !property_exists($this, $name)) {
                $routeName = lcfirst(str_replace('route', '', $name));
                if (!\array_key_exists($routeName, $this->routes) && strlen($routeName) > 0) {
                    $this->routes[$routeName] = $value;
                }
            }
        }
    }
}
