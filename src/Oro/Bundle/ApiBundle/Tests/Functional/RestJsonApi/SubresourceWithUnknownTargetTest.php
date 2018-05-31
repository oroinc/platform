<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;

class SubresourceWithUnknownTargetTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/test_product.yml'
        ]);
    }

    public function testGetSubresource()
    {
        $entityType = $this->getEntityType(TestProduct::class);

        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unregistered-target'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testPostSubresource()
    {
        $entityType = $this->getEntityType(TestProduct::class);

        $response = $this->postSubresource(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unregistered-target'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testPatchSubresource()
    {
        $entityType = $this->getEntityType(TestProduct::class);

        $response = $this->patchSubresource(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unregistered-target'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDeleteSubresource()
    {
        $entityType = $this->getEntityType(TestProduct::class);

        $response = $this->deleteSubresource(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unregistered-target'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testGetRelationship()
    {
        $entityType = $this->getEntityType(TestProduct::class);

        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unregistered-target'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testPostRelationship()
    {
        $entityType = $this->getEntityType(TestProduct::class);

        $response = $this->postRelationship(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unregistered-target'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testPatchRelationship()
    {
        $entityType = $this->getEntityType(TestProduct::class);

        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unregistered-target'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDeleteRelationship()
    {
        $entityType = $this->getEntityType(TestProduct::class);

        $response = $this->deleteRelationship(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unregistered-target'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }
}
