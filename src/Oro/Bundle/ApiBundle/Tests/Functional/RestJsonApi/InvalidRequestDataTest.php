<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;

class InvalidRequestDataTest extends RestJsonApiTestCase
{
    public function testEmptyJsonInRequestData()
    {
        $response = $this->request(
            'POST',
            $this->getUrl(
                $this->getListRouteName(),
                ['entity' => $this->getEntityType(TestProduct::class)]
            ),
            [],
            [],
            ''
        );
        $this->assertResponseContainsValidationError(
            [
                'status' => '400',
                'title'  => 'request data constraint',
                'detail' => 'The request data should not be empty'
            ],
            $response
        );
    }

    public function testInvalidJsonInRequestData()
    {
        $response = $this->request(
            'POST',
            $this->getUrl(
                $this->getListRouteName(),
                ['entity' => $this->getEntityType(TestProduct::class)]
            ),
            [],
            [],
            '{"data": {"type": test"}}'
        );
        $this->assertResponseContainsValidationError(
            [
                'status' => '400',
                'title'  => 'bad request http exception',
                'detail' => 'Invalid json message received.'
                    . ' Parsing error in [1:22]. Expected \'null\'. Got: test.'
            ],
            $response
        );
    }

    public function testInvalidJsonInRequestDataTrailingCommaAfterProperty()
    {
        $response = $this->request(
            'POST',
            $this->getUrl(
                $this->getListRouteName(),
                ['entity' => $this->getEntityType(TestProduct::class)]
            ),
            [],
            [],
            '{"data": {"type": "test", "attributes": {"name": "some name",}}}'
        );
        $this->assertResponseContainsValidationError(
            [
                'status' => '400',
                'title'  => 'bad request http exception',
                'detail' => 'Invalid json message received.'
            ],
            $response
        );
    }

    public function testInvalidJsonWithInvalidUtf8CharacterInRequestData()
    {
        $response = $this->request(
            'POST',
            $this->getUrl(
                $this->getListRouteName(),
                ['entity' => $this->getEntityType(TestProduct::class)]
            ),
            [],
            [],
            '{"data": ▿{"type": test"}}'
        );
        $this->assertResponseContainsValidationError(
            [
                'status' => '400',
                'title'  => 'bad request http exception',
                'detail' => 'Invalid json message received.'
                    . ' Parsing error in [1:10]. Unexpected character for value: ?.'
            ],
            $response
        );
    }
}
