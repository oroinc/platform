<?php

namespace Oro\Bundle\ApiBundle\Processor\Create\JsonApi;

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
        if ($this->validateRequired($data, JsonApiDoc::TYPE, $pointer)) {
            $this->validatePrimaryDataObjectType($data, $pointer);
        }
    }
}
