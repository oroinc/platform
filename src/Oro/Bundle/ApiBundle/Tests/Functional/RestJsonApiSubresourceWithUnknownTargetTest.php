<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestProduct;

class RestJsonApiSubresourceWithUnknownTargetTest extends RestJsonApiTestCase
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
            ['entity' => $entityType, 'id' => '@test_product1->id', 'association' => 'search'],
            [],
            [],
            false
        );

        $this->assertResponseContains(
            [
                'code' => 500,
                // @todo: should be uncommented in scope of BAP-9473.
                //'message' => 'The result does not exist.'
            ],
            $response
        );
    }

    public function testGetRelationship()
    {
        $entityType = $this->getEntityType(TestProduct::class);

        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => '@test_product1->id', 'association' => 'search'],
            [],
            [],
            false
        );

        $this->assertResponseContains(
            [
                'code' => 500,
                // @todo: should be uncommented in scope of BAP-9473.
                //'message' => 'The result does not exist.'
            ],
            $response
        );
    }

    public function testPostRelationship()
    {
        $entityType = $this->getEntityType(TestProduct::class);

        $response = $this->postRelationship(
            ['entity' => $entityType, 'id' => '@test_product1->id', 'association' => 'search'],
            [
                'data' => [
                    ['type' => 'search', 'id' => '1']
                ]
            ],
            [],
            false
        );

        $this->assertResponseContains(
            [
                'errors' => [
                    [
                        'status' => '500',
                        'title'  => 'runtime exception',
                        'detail' => \sprintf(
                            'The metadata for association "%s::search" does not exist.',
                            TestProduct::class
                        )
                    ]
                ]
            ],
            $response
        );
    }

    public function testPatchRelationship()
    {
        $entityType = $this->getEntityType(TestProduct::class);

        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '@test_product1->id', 'association' => 'search'],
            [
                'data' => [
                    ['type' => 'search', 'id' => '1']
                ]
            ],
            [],
            false
        );

        $this->assertResponseContains(
            [
                'errors' => [
                    [
                        'status' => '500',
                        'title'  => 'runtime exception',
                        'detail' => \sprintf(
                            'The metadata for association "%s::search" does not exist.',
                            TestProduct::class
                        )
                    ]
                ]
            ],
            $response
        );
    }

    public function testDeleteRelationship()
    {
        $entityType = $this->getEntityType(TestProduct::class);

        $response = $this->deleteRelationship(
            ['entity' => $entityType, 'id' => '@test_product1->id', 'association' => 'search'],
            [
                'data' => [
                    ['type' => 'search', 'id' => '1']
                ]
            ],
            [],
            false
        );

        $this->assertResponseContains(
            [
                'errors' => [
                    [
                        'status' => '500',
                        'title'  => 'runtime exception',
                        'detail' => \sprintf(
                            'The metadata for association "%s::search" does not exist.',
                            TestProduct::class
                        )
                    ]
                ]
            ],
            $response
        );
    }
}
