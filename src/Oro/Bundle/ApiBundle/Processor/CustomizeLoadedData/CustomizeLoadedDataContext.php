<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\Extra\HateoasConfigExtra;
use Oro\Bundle\ApiBundle\Processor\CustomizeDataContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The execution context for processors for "customize_loaded_data" action.
 */
class CustomizeLoadedDataContext extends CustomizeDataContext
{
    /** a flag indicates whether "customize_loaded_data" action is executed for a relationship */
    private const IDENTIFIER_ONLY = 'identifier_only';

    /** @var ConfigExtraInterface[] */
    private array $configExtras = [];
    private ?bool $isHateoasEnabled = null;

    /**
     * Gets the response data.
     *
     * The format of the data for a single item: [field name => field value, ...]
     * The format of the data for a collection: [[field name => field value, ...], ...]
     *
     * This method is a alias for getResult, but with strict return data type.
     */
    public function getData(): array
    {
        return $this->getResult();
    }

    /**
     * Sets the response data.
     *
     * This method is a alias for setResult, but with strict data type of the parameter.
     */
    public function setData(array $data): void
    {
        $this->setResult($data);
    }

    /**
     * Indicates whether "customize_loaded_data" action is executed for a relationship,
     * it means that only identifier field should be returned in response data.
     */
    public function isIdentifierOnly(): bool
    {
        return (bool)$this->get(self::IDENTIFIER_ONLY);
    }

    /**
     * Sets a flag indicates whether "customize_loaded_data" action is executed for a relationship.
     * In this case only identifier field should be returned in response data.
     */
    public function setIdentifierOnly(bool $identifierOnly): void
    {
        $this->set(self::IDENTIFIER_ONLY, $identifierOnly);
    }

    /**
     * Gets the name under which the given field should be represented in response data.
     */
    public function getResultFieldName(string $propertyPath): ?string
    {
        $config = $this->getConfig();
        if (null === $config) {
            return $propertyPath;
        }

        $fieldName = $config->findFieldNameByPropertyPath($propertyPath);
        if (!$fieldName) {
            $field = $config->getField($propertyPath);
            if (null === $field || ConfigUtil::IGNORE_PROPERTY_PATH === $field->getPropertyPath()) {
                $fieldName = $propertyPath;
            }
        }

        return $fieldName;
    }

    /**
     * Gets the value of the given entity field from response data.
     */
    public function getResultFieldValue(string $propertyName, array $data): mixed
    {
        $fieldName = $this->getResultFieldName($propertyName);
        if (!$fieldName || !\array_key_exists($fieldName, $data)) {
            return null;
        }

        return $data[$fieldName];
    }

    /**
     * Gets the value of an entity field by the given property path from response data.
     */
    public function getResultFieldValueByPropertyPath(string $propertyPath, array $data): mixed
    {
        $config = $this->getConfig();
        $path = ConfigUtil::explodePropertyPath($propertyPath);
        $lastPropertyName = array_pop($path);
        foreach ($path as $propertyName) {
            if (null !== $config) {
                $fieldName = $config->findFieldNameByPropertyPath($propertyName);
                if (!$fieldName) {
                    return null;
                }
                $config = $config->getField($fieldName)->getTargetEntity();
                $propertyName = $fieldName;
            }
            if (!\array_key_exists($propertyName, $data)) {
                return null;
            }
            $data = $data[$propertyName];
            if (!\is_array($data)) {
                return null;
            }
        }

        if (null !== $config) {
            $fieldName = $config->findFieldNameByPropertyPath($lastPropertyName);
            if (!$fieldName) {
                return null;
            }
            $lastPropertyName = $fieldName;
        }

        return $data[$lastPropertyName] ?? null;
    }

    /**
     * Indicates whether the given field is requested to be returned in response data
     * and it does not exist in the given response data yet if the response data is specified.
     * This method takes into account whether the "customize_loaded_data" action is executed
     * for a relationship (in this case only identifier field is returned)
     * or for primary or included resource (in this case a list of returned fields
     * can be limited, e.g. using "fields" filter in REST API that conforms the JSON:API specification).
     * @link http://jsonapi.org/format/#fetching-sparse-fieldsets
     *
     * @param string|null $fieldName The name under which a field should be represented in response data
     * @param array|null  $data      Response data
     *
     * @return bool TRUE if the given field is requested to be returned in response data,
     *              and it does not exist in the given response data, if it is specified
     */
    public function isFieldRequested(?string $fieldName, ?array $data = null): bool
    {
        if (!$fieldName) {
            return false;
        }

        if (null !== $data && \array_key_exists($fieldName, $data)) {
            return false;
        }

        $config = $this->getConfig();
        if (null === $config) {
            return false;
        }

        $field = $config->getField($fieldName);

        return null !== $field && !$field->isExcluded();
    }

    /**
     * Indicates whether at least one of the given fields is requested to be returned in response data
     * and it does not exist in the given response data yet if the response data is specified.
     *
     * @see isFieldRequested
     *
     * @param string[]   $fieldNames The names under which fields should be represented in response data
     * @param array|null $data       Response data
     *
     * @return bool TRUE if at least one of the given fields is requested to be returned in response data,
     *              and it does not exist in the given response data, if it is specified
     */
    public function isAtLeastOneFieldRequested(array $fieldNames, ?array $data = null): bool
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
     * in at least one element of response collection data and it does not exist in the given response data yet.
     *
     * @see isFieldRequested
     *
     * @param array  $data      Response data
     * @param string $fieldName The name under which a field should be represented in response data
     *
     * @return bool TRUE if the given field is requested to be returned in at least one element
     *              of response collection data, and it does not exist in the given response data
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
     * in at least one element of response collection data and it does not exist in the given response data yet.
     *
     * @see isAtLeastOneFieldRequested
     *
     * @param array    $data       Response data
     * @param string[] $fieldNames The names under which fields should be represented in response data
     *
     * @return bool TRUE if at least one of the given fields is requested to be returned in at least one element
     *              of response collection data, and it does not exist in the given response data
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
    public function getIdentifierValues(array $data, string $identifierFieldName): mixed
    {
        $ids = [];
        foreach ($data as $item) {
            $ids[] = $item[$identifierFieldName];
        }

        return $ids;
    }

    /**
     * Checks whether some configuration data for a customizing entity is requested.
     */
    public function hasConfigExtra(string $extraName): bool
    {
        foreach ($this->configExtras as $extra) {
            if ($extra->getName() === $extraName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets a request for configuration data of a customizing entity by its name.
     */
    public function getConfigExtra(string $extraName): ?ConfigExtraInterface
    {
        foreach ($this->configExtras as $extra) {
            if ($extra->getName() === $extraName) {
                return $extra;
            }
        }

        return null;
    }

    /**
     * Gets a list of requests for configuration data of a customizing entity.
     *
     * @return ConfigExtraInterface[]
     */
    public function getConfigExtras(): array
    {
        return $this->configExtras;
    }

    /**
     * Sets a list of requests for configuration data of a customizing entity.
     *
     * @param ConfigExtraInterface[] $configExtras
     */
    public function setConfigExtras(array $configExtras): void
    {
        $this->configExtras = $configExtras;
        $this->isHateoasEnabled = null;
    }

    /**
     * Indicates whether HATEOAS is enabled.
     */
    public function isHateoasEnabled(): bool
    {
        if (null === $this->isHateoasEnabled) {
            $this->isHateoasEnabled = false;
            foreach ($this->configExtras as $extra) {
                if ($extra instanceof HateoasConfigExtra) {
                    $this->isHateoasEnabled = true;
                }
            }
        }

        return $this->isHateoasEnabled;
    }
}
