<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestAllDataTypes;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class HttpMethodOverrideTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/supported_data_types.yml'
        ]);
    }

    public function testHttpMethodOverride(): void
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $response = $this->request(
            'POST',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType]),
            ['filter' => ['fieldInt' => ['neq' => 2]]],
            ['HTTP_X-HTTP-Method-Override' => 'GET']
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, $this->getResponseContentType());

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => $entityType, 'id' => '<toString(@TestItem1->id)>'],
                    ['type' => $entityType, 'id' => '<toString(@TestItem3->id)>'],
                    ['type' => $entityType, 'id' => '<toString(@AnotherItem->id)>'],
                    ['type' => $entityType, 'id' => '<toString(@EmptyItem->id)>']
                ]
            ],
            $response
        );
    }

    public function testHttpMethodOverrideWithLowercaseValueInHeader(): void
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $response = $this->request(
            'POST',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType]),
            ['filter' => ['fieldInt' => ['neq' => 2]]],
            ['HTTP_X-HTTP-Method-Override' => 'get']
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, $this->getResponseContentType());

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => $entityType, 'id' => '<toString(@TestItem1->id)>'],
                    ['type' => $entityType, 'id' => '<toString(@TestItem3->id)>'],
                    ['type' => $entityType, 'id' => '<toString(@AnotherItem->id)>'],
                    ['type' => $entityType, 'id' => '<toString(@EmptyItem->id)>']
                ]
            ],
            $response
        );
    }

    public function testHttpMethodOverrideWithNotStructuredData(): void
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $response = $this->request(
            'POST',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType]),
            ['filter[fieldInt][neq]' => '2'],
            ['HTTP_X-HTTP-Method-Override' => 'GET']
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, $this->getResponseContentType());

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => $entityType, 'id' => '<toString(@TestItem1->id)>'],
                    ['type' => $entityType, 'id' => '<toString(@TestItem3->id)>'],
                    ['type' => $entityType, 'id' => '<toString(@AnotherItem->id)>'],
                    ['type' => $entityType, 'id' => '<toString(@EmptyItem->id)>']
                ]
            ],
            $response
        );
    }
}
