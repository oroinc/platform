<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiDocumentation;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ApiBundle\Tests\Functional\SpecialFieldsConsistencyTestTrait;

class SpecialFieldsConsistencyTest extends RestJsonApiTestCase
{
    use SpecialFieldsConsistencyTestTrait;

    public function testSpecialFieldsConsistency()
    {
        $this->checkSpecialFieldsConsistency();
    }
}
