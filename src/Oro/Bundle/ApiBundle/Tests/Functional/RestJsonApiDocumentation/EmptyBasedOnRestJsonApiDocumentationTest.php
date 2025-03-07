<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiDocumentation;

use Oro\Bundle\ApiBundle\Tests\Functional\DocumentationTestTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class EmptyBasedOnRestJsonApiDocumentationTest extends RestJsonApiTestCase
{
    use DocumentationTestTrait;

    /** @var string used in DocumentationTestTrait */
    private const VIEW = 'test_empty_rest_api_based_on_rest_json_api';

    public function testDocumentation(): void
    {
        $this->warmUpDocumentationCache();
        $this->assertEmptyDocumentation();
    }
}
