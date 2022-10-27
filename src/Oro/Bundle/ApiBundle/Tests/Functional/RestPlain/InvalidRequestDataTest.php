<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestPlain;

use Oro\Bundle\ApiBundle\Tests\Functional\RestPlainApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;

class InvalidRequestDataTest extends RestPlainApiTestCase
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
                'title'  => 'bad request http exception',
                'detail' => 'Invalid json message received.'
                    . ' Parsing error in [1:22]. Expected \'null\'. Got: test.'
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
                'title'  => 'bad request http exception',
                'detail' => 'Invalid json message received.'
                    . ' Parsing error in [1:10]. Unexpected character for value: ?.'
            ],
            $response
        );
    }
}
