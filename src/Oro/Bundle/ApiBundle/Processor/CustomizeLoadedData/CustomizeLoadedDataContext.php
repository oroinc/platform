<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Processor\CustomizeDataContext;

/**
 * The execution context for processors for "customize_loaded_data" action.
 */
class CustomizeLoadedDataContext extends CustomizeDataContext
{
    /** a flag indicates whether "customize_loaded_data" action is executed for a relationship */
    private const IDENTIFIER_ONLY = 'identifier_only';

    /**
     * Indicates whether "customize_loaded_data" action is executed for a relationship,
     * it means that only identifier field should be returned in response data.
     *
     * @return bool
     */
    public function isIdentifierOnly(): bool
    {
        return (bool)$this->get(self::IDENTIFIER_ONLY);
    }

    /**
     * Sets a flag indicates whether "customize_loaded_data" action is executed for a relationship.
     * In this case only identifier field should be returned in response data.
     *
     * @param bool $identifierOnly
     */
    public function setIdentifierOnly(bool $identifierOnly): void
    {
        $this->set(self::IDENTIFIER_ONLY, $identifierOnly);
    }

    /**
     * Gets the name under which the given field should be represented in response data.
     *
     * @param string $propertyPath             The name of an entity field
     * @param bool   $usePropertyPathByDefault Whether the given property path should be returned
     *                                         if a field does not exist
     *
     * @return string|null
     */
    public function getResultFieldName(string $propertyPath, bool $usePropertyPathByDefault = false): ?string
    {
        $config = $this->getConfig();
        if (null === $config) {
            return $usePropertyPathByDefault ? $propertyPath : null;
        }

        $fieldName = $config->findFieldNameByPropertyPath($propertyPath);
        if (!$fieldName && $usePropertyPathByDefault) {
            $fieldName = $propertyPath;
        }

        return $fieldName;
    }

    /**
     * Gets the value of the given entity field from response data.
     *
     * @param string     $propertyName The name of an entity field
     * @param array|null $data         Response data
     *
     * @return mixed
     */
    public function getResultFieldValue(string $propertyName, array $data)
    {
        $fieldName = $this->getResultFieldName($propertyName);
        if (!$fieldName || !array_key_exists($fieldName, $data)) {
            return null;
        }

        return $data[$fieldName];
    }

    /**
     * Indicates whether the given field is requested to be returned in response data.
     * This method takes into account whether the "customize_loaded_data" action is executed
     * for a relationship (in this case only identifier field is returned)
     * or for primary or included resource (in this case a list of returned fields
     * can be limited, e.g. using "fields" filter in REST API conforms JSON.API specification).
     * @link http://jsonapi.org/format/#fetching-sparse-fieldsets
     *
     * @param string|null $fieldName The name under which a field should be represented in response data
     * @param array|null  $data      Response data
     *
     * @return bool TRUE if the given field is requested to be returned in response data,
     *              and it does not exist in the given response data, if it is specified
     */
    public function isFieldRequested(?string $fieldName, array $data = null): bool
    {
        if (!$fieldName) {
            return false;
        }

        $config = $this->getConfig();
        if (null === $config) {
            return false;
        }

        $field = $config->getField($fieldName);
        $isRequested = null !== $field && !$field->isExcluded();
        if ($isRequested && null !== $data && \array_key_exists($fieldName, $data)) {
            $isRequested = false;
        }

        return $isRequested;
    }

    /**
     * Indicates whether at least one of the given fields is requested to be returned in response data.
     * @see isFieldRequested
     *
     * @param string[]   $fieldNames The names under which fields should be represented in response data
     * @param array|null $data       Response data
     *
     * @return bool
     */
    public function isAtLeastOneFieldRequested(array $fieldNames, array $data = null): bool
    {
        foreach ($fieldNames as $fieldName) {
            if ($this->isFieldRequested($fieldName, $data)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicates whether the given field is requested to be returned
     * in at least one element of response collection data.
     * @see isFieldRequested
     *
     * @param string $fieldName The name under which a field should be represented in response data
     * @param array  $data      Response data
     *
     * @return bool
     */
    public function isFieldRequestedForCollection(string $fieldName, array $data): bool
    {
        foreach ($data as $item) {
            if ($this->isFieldRequested($fieldName, $item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicates whether at least one of the given fields is requested to be returned
     * in at least one element of response collection data.
     * @see isAtLeastOneFieldRequested
     *
     * @param string[] $fieldNames The names under which fields should be represented in response data
     * @param array    $data       Response data
     *
     * @return bool
     */
    public function isAtLeastOneFieldRequestedForCollection(array $fieldNames, array $data): bool
    {
        foreach ($data as $item) {
            if ($this->isAtLeastOneFieldRequested($fieldNames, $item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extracts the entity identifier values from response data that contains a collection of entity data.
     *
     * @param array  $data                Response data
     * @param string $identifierFieldName The name of an entity identifier field
     *
     * @return mixed
     */
    public function getIdentifierValues(array $data, string $identifierFieldName)
    {
        $ids = [];
        foreach ($data as $item) {
            $ids[] = $item[$identifierFieldName];
        }

        return $ids;
    }
}
