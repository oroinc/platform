<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\ResourceConsistencyTestTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class ResourceConsistencyTest extends RestJsonApiTestCase
{
    use ResourceConsistencyTestTrait;

    /**
     * @param string   $entityClass
     * @param string[] $excludedActions
     *
     * @dataProvider getEntities
     */
    public function testResourceConsistency($entityClass, $excludedActions)
    {
        $this->checkResourceConsistency($entityClass, $excludedActions);
    }
}
