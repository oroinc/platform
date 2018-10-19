<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * This class can be used to validate that the request data contains valid JSON.API object(s).
 * Unlike the RequestDataValidator, this validator checks the primary resource type
 * and identifier (if requested).
 * @link http://jsonapi.org/format/#crud
 */
class TypedRequestDataValidator extends AbstractRequestDataValidator
{
    /** @var callable */
    private $convertEntityTypeToClass;

    /**
     * @param callable $convertEntityTypeToClass function (string $entityType): ?string
     */
    public function __construct($convertEntityTypeToClass)
    {
        $this->convertEntityTypeToClass = $convertEntityTypeToClass;
    }

    /**
     * Validates that the given request data contains a resource object in the "data" section.
     * Also validates that the request data contains an array of resource objects in the "included" section
     * if $allowIncludedResources is TRUE and this section exists.
     * If $allowIncludedResources is FALSE validates that the request data and does not contain "included" section.
     *
     * @param array      $requestData
     * @param bool       $allowIncludedResources
     * @param string     $primaryResourceClass
     * @param mixed|null $primaryResourceId
     *
     * @return Error[]
     */
    public function validateResourceObject(
        array $requestData,
        bool $allowIncludedResources,
        string $primaryResourceClass,
        $primaryResourceId = null
    ): array {
        return $this->doValidation(function () use (
            $requestData,
            $allowIncludedResources,
            $primaryResourceClass,
            $primaryResourceId
        ) {
            if ($this->validateRequestData($requestData, JsonApiDoc::DATA)) {
                $data = $requestData[JsonApiDoc::DATA];
                $pointer = $this->buildPointer(self::ROOT_POINTER, JsonApiDoc::DATA);
                $this->validatePrimaryDataObject($data, $primaryResourceClass, $primaryResourceId, $pointer);
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
     * @param array  $requestData
     * @param bool   $allowIncludedResources
     * @param string $primaryResourceClass
     * @param bool   $requirePrimaryResourceId
     *
     * @return Error[]
     */
    public function validateResourceObjectCollection(
        array $requestData,
        bool $allowIncludedResources,
        string $primaryResourceClass,
        bool $requirePrimaryResourceId = false
    ): array {
        return $this->doValidation(function () use (
            $requestData,
            $allowIncludedResources,
            $primaryResourceClass,
            $requirePrimaryResourceId
        ) {
            if ($this->validateRequestDataCollection($requestData, JsonApiDoc::DATA)) {
                $data = $requestData[JsonApiDoc::DATA];
                $pointer = $this->buildPointer(self::ROOT_POINTER, JsonApiDoc::DATA);
                if (\is_array($data) && !ArrayUtil::isAssoc($data)) {
                    foreach ($data as $key => $item) {
                        $itemPointer = $this->buildPointer($pointer, $key);
                        if (\is_array($item)) {
                            if ($this->validateRequiredNotBlankString($item, JsonApiDoc::TYPE, $itemPointer)) {
                                $this->validatePrimaryDataObjectType($item, $primaryResourceClass, $itemPointer);
                            }
                            if ($requirePrimaryResourceId) {
                                $this->validateRequiredNotBlankString($item, JsonApiDoc::ID, $itemPointer);
                            } else {
                                $this->validateNotBlankString($item, JsonApiDoc::ID, $itemPointer);
                            }
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
     * @param array      $data
     * @param string     $primaryResourceClass
     * @param mixed|null $primaryResourceId
     * @param string     $pointer
     */
    protected function validatePrimaryDataObject(
        array $data,
        string $primaryResourceClass,
        $primaryResourceId,
        string $pointer
    ): void {
        if (null === $primaryResourceId) {
            if ($this->validateRequiredNotBlankString($data, JsonApiDoc::TYPE, $pointer)) {
                $this->validatePrimaryDataObjectType($data, $primaryResourceClass, $pointer);
            }
            $this->validateNotBlankString($data, JsonApiDoc::ID, $pointer);
        } elseif ($this->validateTypeAndIdAreRequiredNotBlankString($data, $pointer)) {
            $this->validatePrimaryDataObjectType($data, $primaryResourceClass, $pointer);
            $this->validatePrimaryDataObjectId($data, $primaryResourceId, $pointer);
        }
    }

    /**
     * @param array  $data
     * @param string $primaryResourceClass
     * @param string $pointer
     *
     * @return bool
     */
    protected function validatePrimaryDataObjectType(
        array $data,
        string $primaryResourceClass,
        string $pointer
    ): bool {
        $isValid = true;
        $dataClassName = \call_user_func($this->convertEntityTypeToClass, $data[JsonApiDoc::TYPE]);
        if ($dataClassName !== $primaryResourceClass) {
            $this->addConflictError(
                $this->buildPointer($pointer, JsonApiDoc::TYPE),
                \sprintf(
                    'The \'%s\' property of the primary data object should match the requested resource',
                    JsonApiDoc::TYPE
                )
            );
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @param array  $data
     * @param mixed  $primaryResourceId
     * @param string $pointer
     *
     * @return bool
     */
    protected function validatePrimaryDataObjectId(array $data, $primaryResourceId, string $pointer): bool
    {
        // do matching only if the identifier is not normalized yet
        if (\is_string($primaryResourceId) && $primaryResourceId !== $data[JsonApiDoc::ID]) {
            $this->addConflictError(
                $this->buildPointer($pointer, JsonApiDoc::ID),
                \sprintf(
                    'The \'%1$s\' property of the primary data object'
                    . ' should match \'%1$s\' parameter of the query sting',
                    JsonApiDoc::ID
                )
            );

            return false;
        }

        return true;
    }

    /**
     * @param string $pointer
     * @param string $message
     */
    protected function addConflictError(string $pointer, string $message): void
    {
        $this->addErrorObject(
            Error::createConflictValidationError($message)
                ->setSource(ErrorSource::createByPointer($pointer))
        );
    }
}
