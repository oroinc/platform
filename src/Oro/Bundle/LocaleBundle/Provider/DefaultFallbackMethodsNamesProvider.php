<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Doctrine\Inflector\Inflector;

/**
 * Provides getters and setters names for default localization for localized fallback value fields.
 */
class DefaultFallbackMethodsNamesProvider
{
    private Inflector $inflector;

    public function __construct(Inflector $inflector)
    {
        $this->inflector = $inflector;
    }

    public function getGetterMethodName(string $fieldName): string
    {
        return $this->getMethodName($fieldName, 'get');
    }

    public function getDefaultGetterMethodName(string $fieldName): string
    {
        return $this->getMethodName($fieldName, 'getDefault');
    }

    public function getDefaultSetterMethodName(string $fieldName): string
    {
        return $this->getMethodName($fieldName, 'setDefault');
    }

    private function getMethodName(string $fieldName, string $prefix): string
    {
        return $prefix . \ucfirst(
            $this->inflector->singularize(
                $this->inflector->camelize($fieldName)
            )
        );
    }
}
