<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * The base class for the JSON:API request data typed and untyped validators.
 */
abstract class AbstractRequestDataValidator extends AbstractBaseRequestDataValidator
{
    /**
     * Validates that the given request data contains "meta" section
     * and does not contains the "data" and "included" sections.
     *
     * @param array $requestData
     *
     * @return Error[]
     */
    public function validateMetaObject(array $requestData): array
    {
        return $this->doValidation(function () use ($requestData) {
            $this->validateJsonApiSection($requestData);
            $this->validateLinksSection($requestData);
            if ($this->validateRequestData($requestData, JsonApiDoc::META)) {
                $this->validateSectionNotExist($requestData, JsonApiDoc::DATA);
                $this->validateSectionNotExist($requestData, JsonApiDoc::INCLUDED);
            }
        });
    }

    protected function validateRequestData(array $data, string $rootSection): bool
    {
        $isValid = true;
        if (!\array_key_exists($rootSection, $data)) {
            $this->addError(
                $this->buildPointer(self::ROOT_POINTER, $rootSection),
                sprintf('The primary %s object should exist', strtolower($rootSection))
            );
            $isValid = false;
        } elseif (empty($data[$rootSection])) {
            $this->addError(
                $this->buildPointer(self::ROOT_POINTER, $rootSection),
                sprintf('The primary %s object should not be empty', strtolower($rootSection))
            );
            $isValid = false;
        }

        return $isValid;
    }

    protected function validateResourceObjectStructure(array $data, string $pointer): bool
    {
        $isValid = true;

        if (!\array_key_exists(0, $data)) {
            $invalidProperties = array_diff(array_keys($data), $this->getResourceObjectProperties());
            if (!empty($invalidProperties)) {
                foreach ($invalidProperties as $invalidProperty) {
                    $this->addError(
                        $pointer,
                        sprintf('The \'%s\' property is not allowed for a resource object', $invalidProperty)
                    );
                }
                $isValid = false;
            }
            $this->validateMetaSection($data, $pointer);
            $this->validateLinksSection($data, $pointer);
        }

        return $isValid;
    }

    /**
     * Returns allowed properties for a resource object.
     * @link https://jsonapi.org/format/#document-resource-objects
     *
     * @return string[]
     */
    protected function getResourceObjectProperties(): array
    {
        return [
            JsonApiDoc::TYPE,
            JsonApiDoc::ID,
            JsonApiDoc::META,
            JsonApiDoc::ATTRIBUTES,
            JsonApiDoc::RELATIONSHIPS,
            JsonApiDoc::LINKS
        ];
    }

    protected function validateRequestDataCollection(array $data, string $rootSection): bool
    {
        $isValid = true;
        if (!\array_key_exists($rootSection, $data)) {
            $this->addError(
                $this->buildPointer(self::ROOT_POINTER, $rootSection),
                sprintf('The primary %s object collection should exist', strtolower($rootSection))
            );
            $isValid = false;
        } elseif (empty($data[$rootSection])) {
            $this->addError(
                $this->buildPointer(self::ROOT_POINTER, $rootSection),
                sprintf('The primary %s object collection should not be empty', strtolower($rootSection))
            );
            $isValid = false;
        }

        return $isValid;
    }

    protected function validateAttributesAndRelationships(array $data, string $pointer): void
    {
        if (\array_key_exists(JsonApiDoc::ATTRIBUTES, $data)) {
            $this->validateArray($data, JsonApiDoc::ATTRIBUTES, $pointer, true, true);
        }
        if (\array_key_exists(JsonApiDoc::RELATIONSHIPS, $data)
            && $this->validateArray($data, JsonApiDoc::RELATIONSHIPS, $pointer, true, true)
        ) {
            $this->validateRelationships(
                $data[JsonApiDoc::RELATIONSHIPS],
                $this->buildPointer($pointer, JsonApiDoc::RELATIONSHIPS)
            );
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function validateRelationships(array $data, string $pointer): bool
    {
        $isValid = true;
        foreach ($data as $relationName => $relation) {
            $relationPointer = $this->buildPointer($pointer, $relationName);
            if (!\is_array($relation) || !\array_key_exists(JsonApiDoc::DATA, $relation)) {
                $this->addError(
                    $relationPointer,
                    sprintf('The relationship should have \'%s\' property', JsonApiDoc::DATA)
                );
                $isValid = false;
                continue;
            }
            if (null === $relation[JsonApiDoc::DATA]) {
                continue;
            }
            if (!$this->validateArray($relation, JsonApiDoc::DATA, $relationPointer)) {
                $isValid = false;
                continue;
            }

            $relationData = $relation[JsonApiDoc::DATA];
            $relationDataPointer = $this->buildPointer($relationPointer, JsonApiDoc::DATA);
            if ($relationData) {
                if (!ArrayUtil::isAssoc($relationData)) {
                    foreach ($relationData as $key => $value) {
                        if ($this->validateTypeAndIdAreRequiredNotBlankString(
                            $value,
                            $this->buildPointer($relationDataPointer, $key)
                        )) {
                            $isValid = false;
                        }
                    }
                } elseif (!$this->validateTypeAndIdAreRequiredNotBlankString($relationData, $relationDataPointer)) {
                    $isValid = false;
                }
            }
        }

        return $isValid;
    }

    protected function validateIncludedResources(array $data): void
    {
        if (\array_key_exists(JsonApiDoc::INCLUDED, $data)
            && $this->validateArray($data, JsonApiDoc::INCLUDED, self::ROOT_POINTER, true)
        ) {
            $includedPointer = $this->buildPointer(self::ROOT_POINTER, JsonApiDoc::INCLUDED);
            foreach ($data[JsonApiDoc::INCLUDED] as $key => $item) {
                $pointer = $this->buildPointer($includedPointer, $key);
                if (\is_array($item)) {
                    $this->validateResourceObjectStructure($item, $pointer);
                    $this->validateTypeAndIdAreRequiredNotBlankString($item, $pointer);
                } else {
                    $this->addError($pointer, 'The related resource should be an object');
                }
            }
        }
    }
}
