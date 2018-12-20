<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * This class can be used to validate that the request data contains valid JSON.API object(s).
 * @link http://jsonapi.org/format/#crud
 */
class RequestDataValidator extends AbstractRequestDataValidator
{
    /**
     * Validates that the given request data contains a resource object in the "data" section.
     * Also validates that the request data contains an array of resource objects in the "included" section
     * if $allowIncludedResources is TRUE and this section exists.
     * If $allowIncludedResources is FALSE validates that the request data and does not contain "included" section.
     *
     * @param array $requestData
     * @param bool  $allowIncludedResources
     * @param bool  $requirePrimaryResourceId
     *
     * @return Error[]
     */
    public function validateResourceObject(
        array $requestData,
        bool $allowIncludedResources,
        bool $requirePrimaryResourceId = false
    ): array {
        return $this->doValidation(function () use (
            $requestData,
            $allowIncludedResources,
            $requirePrimaryResourceId
        ) {
            if ($this->validateRequestData($requestData, JsonApiDoc::DATA)) {
                $data = $requestData[JsonApiDoc::DATA];
                $pointer = $this->buildPointer(self::ROOT_POINTER, JsonApiDoc::DATA);
                $this->validatePrimaryDataObject($data, $pointer, $requirePrimaryResourceId);
                $this->validateAttributesAndRelationships($data, $pointer);
                if ($allowIncludedResources) {
                    $this->validateIncludedResources($requestData);
                } else {
                    $this->validateSectionNotExist($requestData, JsonApiDoc::INCLUDED);
                }
            }
        });
    }

    /**
     * Validates that the given request data contains an array of resource objects in the "data" section.
     * Also validates that the request data contains an array of resource objects in the "included" section
     * if $allowIncludedResources is TRUE and this section exists.
     * If $allowIncludedResources is FALSE validates that the request data and does not contain "included" section.
     *
     * @param array $requestData
     * @param bool  $allowIncludedResources
     * @param bool  $requirePrimaryResourceId
     *
     * @return Error[]
     */
    public function validateResourceObjectCollection(
        array $requestData,
        bool $allowIncludedResources,
        bool $requirePrimaryResourceId = false
    ): array {
        return $this->doValidation(function () use (
            $requestData,
            $allowIncludedResources,
            $requirePrimaryResourceId
        ) {
            if ($this->validateRequestDataCollection($requestData, JsonApiDoc::DATA)) {
                $data = $requestData[JsonApiDoc::DATA];
                $pointer = $this->buildPointer(self::ROOT_POINTER, JsonApiDoc::DATA);
                if (\is_array($data) && !ArrayUtil::isAssoc($data)) {
                    foreach ($data as $key => $item) {
                        $itemPointer = $this->buildPointer($pointer, $key);
                        if (\is_array($item)) {
                            $this->validatePrimaryDataObject($item, $itemPointer, $requirePrimaryResourceId);
                            $this->validateAttributesAndRelationships($item, $itemPointer);
                        } else {
                            $this->addError($itemPointer, 'The primary resource object should be an object');
                        }
                    }
                } else {
                    $this->addError($pointer, 'The primary data object collection should be an array');
                }
                if ($allowIncludedResources) {
                    $this->validateIncludedResources($requestData);
                } else {
                    $this->validateSectionNotExist($requestData, JsonApiDoc::INCLUDED);
                }
            }
        });
    }

    /**
     * @param array  $data
     * @param string $pointer
     * @param bool   $requirePrimaryResourceId
     */
    protected function validatePrimaryDataObject(array $data, string $pointer, bool $requirePrimaryResourceId): void
    {
        $this->validateRequiredNotBlankString($data, JsonApiDoc::TYPE, $pointer);
        if ($requirePrimaryResourceId) {
            $this->validateRequiredNotBlankString($data, JsonApiDoc::ID, $pointer);
        } else {
            $this->validateNotBlankString($data, JsonApiDoc::ID, $pointer);
        }
    }
}
