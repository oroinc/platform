<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * This class can be used to validate that the request data contains valid JSON.API relationship object(s).
 * @link http://jsonapi.org/format/#crud-updating-relationships
 */
class RelationshipRequestDataValidator extends AbstractBaseRequestDataValidator
{
    /**
     * Validates that the given request data contains a resource identifier object in the "data" section.
     *
     * @param array $requestData
     *
     * @return Error[]
     */
    public function validateResourceIdentifierObject(array $requestData): array
    {
        return $this->doValidation(function () use ($requestData) {
            if ($this->validateRequestData($requestData, JsonApiDoc::DATA)) {
                $data = $requestData[JsonApiDoc::DATA];
                $pointer = $this->buildPointer(self::ROOT_POINTER, JsonApiDoc::DATA);
                $this->validatePrimarySingleItemDataObject($data, $pointer);
            }
        });
    }

    /**
     * Validates that the given request data contains an array of resource identifier objects in the "data" section.
     *
     * @param array $requestData
     *
     * @return Error[]
     */
    public function validateResourceIdentifierObjectCollection(array $requestData): array
    {
        return $this->doValidation(function () use ($requestData) {
            if ($this->validateRequestData($requestData, JsonApiDoc::DATA)) {
                $data = $requestData[JsonApiDoc::DATA];
                $pointer = $this->buildPointer(self::ROOT_POINTER, JsonApiDoc::DATA);
                $this->validatePrimaryCollectionDataObject($data, $pointer);
            }
        });
    }

    /**
     * @param array  $data
     * @param string $rootSection
     *
     * @return bool
     */
    protected function validateRequestData(array $data, string $rootSection): bool
    {
        if (!\array_key_exists($rootSection, $data)) {
            $this->addError(
                $this->buildPointer(self::ROOT_POINTER, $rootSection),
                \sprintf('The "%s" top-level section should exist', $rootSection)
            );

            return false;
        }

        return true;
    }

    /**
     * @param mixed  $data
     * @param string $pointer
     */
    protected function validatePrimarySingleItemDataObject($data, string $pointer): void
    {
        if (null === $data) {
            return;
        }
        if (!\is_array($data)) {
            $this->addError(
                $pointer,
                'The resource identifier object should be NULL or an object'
            );

            return;
        }

        $this->validateRelationshipObject($data, $pointer);
    }

    /**
     * @param mixed  $data
     * @param string $pointer
     */
    protected function validatePrimaryCollectionDataObject($data, string $pointer): void
    {
        if (!\is_array($data) || ArrayUtil::isAssoc($data)) {
            $this->addError(
                $pointer,
                'The list of resource identifier objects should be an array'
            );

            return;
        }

        foreach ($data as $key => $value) {
            if (\is_array($value)) {
                $this->validateRelationshipObject($value, $this->buildPointer($pointer, $key));
            } else {
                $this->addError(
                    $this->buildPointer($pointer, $key),
                    'The resource identifier object should be an object'
                );
            }
        }
    }

    /**
     * @param array  $data
     * @param string $pointer
     */
    protected function validateRelationshipObject(array $data, string $pointer): void
    {
        if (empty($data)) {
            $this->addError(
                $pointer,
                'The resource identifier object should be not empty object'
            );

            return;
        }
        if (!ArrayUtil::isAssoc($data)) {
            $this->addError(
                $pointer,
                'The resource identifier object should be an object'
            );

            return;
        }

        $this->validateTypeAndIdAreRequiredNotBlankString($data, $pointer);
    }
}
