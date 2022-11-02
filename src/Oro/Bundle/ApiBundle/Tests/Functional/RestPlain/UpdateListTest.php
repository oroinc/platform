<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestPlain;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestPlainApiTestCase;

class UpdateListTest extends RestPlainApiTestCase
{
    public function testTryToUpdateListRequest()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->cpatch(['entity' => $entityType], [], [], false);
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, POST, DELETE');
    }
}
