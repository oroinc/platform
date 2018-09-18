<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestWithoutIdGenerator;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class CreateEntityWithoutIdGeneratorTest extends RestJsonApiTestCase
{
    public function testCreateWithId()
    {
        $entityType = $this->getEntityType(TestWithoutIdGenerator::class);
        $entityId = 'entity1';
        $entityName = 'test name';

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => $entityId,
                'attributes' => [
                    'name' => $entityName
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);
        $this->assertResponseContains($data, $response);

        // test that the entity was created in the database
        $this->getEntityManager()->clear();
        $createdEntity = $this->getEntityManager()->find(TestWithoutIdGenerator::class, $entityId);
        self::assertNotNull($createdEntity);
        self::assertEquals($entityId, $createdEntity->getId());
        self::assertEquals($entityName, $createdEntity->getName());
    }

    public function testCreateWithoutId()
    {
        $entityType = $this->getEntityType(TestWithoutIdGenerator::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'attributes' => [
                    'name' => 'test name'
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'entity identifier constraint',
                'detail' => 'The identifier is mandatory',
                'source' => ['pointer' => '/data/id']
            ],
            $response
        );
    }

    public function testCreateWhenEntityWithSpecifiedIdAlreadyExists()
    {
        $entityType = $this->getEntityType(TestWithoutIdGenerator::class);
        $entityId = 'entity1';

        $existingEntity = new TestWithoutIdGenerator();
        $existingEntity->setId($entityId);
        $existingEntity->setName('existing entity');
        $this->getEntityManager()->persist($existingEntity);
        $this->getEntityManager()->flush($existingEntity);
        $this->getEntityManager()->clear();

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => $entityId,
                'attributes' => [
                    'name' => 'new entity'
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'conflict constraint',
                'detail' => 'The entity already exists'
            ],
            $response,
            Response::HTTP_CONFLICT
        );
    }
}
