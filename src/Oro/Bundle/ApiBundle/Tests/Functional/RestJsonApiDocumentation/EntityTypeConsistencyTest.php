<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiDocumentation;

use Oro\Bundle\ApiBundle\Tests\Functional\EntityTypeConsistencyTestTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class EntityTypeConsistencyTest extends RestJsonApiTestCase
{
    use EntityTypeConsistencyTestTrait;

    public function testEntityTypeConsistency()
    {
        $this->checkEntityTypeConsistency();
    }
}
