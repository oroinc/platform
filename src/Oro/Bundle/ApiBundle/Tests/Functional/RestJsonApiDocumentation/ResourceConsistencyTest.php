<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiDocumentation;

use Oro\Bundle\ApiBundle\Tests\Functional\ResourceConsistencyTestTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class ResourceConsistencyTest extends RestJsonApiTestCase
{
    use ResourceConsistencyTestTrait;

    public function testResourceConsistency()
    {
        $this->runForEntities(function (string $entityClass, array $excludedActions) {
            $this->checkResourceConsistency($entityClass, $excludedActions);
        });
    }
}
