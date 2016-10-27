<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\ProcessIncludedObjects;

class ProcessIncludedObjectsStub extends ProcessIncludedObjects
{
    /**
     * {@inheritdoc}
     */
    protected function fixErrorPath(Error $error, $objectPath)
    {
    }
}
