<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiDocumentation;

use Oro\Bundle\ApiBundle\Tests\Functional\DocumentationTestTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class EmptyDocumentationTest extends RestJsonApiTestCase
{
    use DocumentationTestTrait;

    /** @var string used in DocumentationTestTrait */
    private const VIEW = 'test_empty_rest_api';

    public function testDocumentation(): void
    {
        $this->warmUpDocumentationCache();
        $this->assertEmptyDocumentation();
    }
}
