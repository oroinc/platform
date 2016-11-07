<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\ValidateRequestData;

class ValidateRequestDataStub extends ValidateRequestData
{
    /**
     * {@inheritdoc}
     */
    protected function validatePrimaryDataObject(array $data, $pointer)
    {
    }
}
