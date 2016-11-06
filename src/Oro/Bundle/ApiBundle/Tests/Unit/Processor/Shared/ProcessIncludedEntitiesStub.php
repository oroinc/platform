<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\ProcessIncludedEntities;

class ProcessIncludedEntitiesStub extends ProcessIncludedEntities
{
    /**
     * {@inheritdoc}
     */
    protected function fixErrorPath(Error $error, $entityPath)
    {
    }
}
