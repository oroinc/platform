<?php

namespace Oro\Bundle\ApiBundle\Processor\Update\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\ValidateRequestData as BaseProcessor;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;

/**
 * Validates that the request data contains valid JSON.API object.
 */
class ValidateRequestData extends BaseProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function validatePrimaryDataObject(array $data, $pointer)
    {
        if ($this->validateResourceObject($data, $pointer)) {
            $this->validatePrimaryDataObjectId($data, $pointer);
            $this->validatePrimaryDataObjectType($data, $pointer);
        }
    }

    /**
     * @param array  $data
     * @param string $pointer
     *
     * @return bool
     */
    protected function validatePrimaryDataObjectId(array $data, $pointer)
    {
        if ($this->context->getId() !== $data[JsonApiDoc::ID]) {
            $this->addError(
                $this->buildPointer($pointer, JsonApiDoc::ID),
                sprintf(
                    'The \'%1$s\' property of the primary data object'
                    . ' should match \'%1$s\' parameter of the query sting',
                    JsonApiDoc::ID
                )
            );

            return false;
        }

        return true;
    }
}
