<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class MetaPropertyTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/test_department.yml'
        ]);
        $this->appendEntityConfig(
            TestDepartment::class,
            [
                'fields' => [
                    'title' => [
                        'meta_property' => true,
                        'form_options'  => [
                            'constraints' => [['NotBlank' => null]]
                        ]
                    ]
                ]
            ]
        );
    }

    public function testGetList()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->cget(['entity' => $entityType]);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id'   => '<toString(@entity1->id)>',
                        'meta' => [
                            'title' => 'Entity 1'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->get(['entity' => $entityType, 'id' => '<toString(@entity1->id)>']);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id'   => '<toString(@entity1->id)>',
                    'meta' => [
                        'title' => 'Entity 1'
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreate()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $data = [
            'data' => [
                'type' => $entityType,
                'meta' => [
                    'title' => 'New Entity'
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $this->assertResponseContains($data, $response);

        /** @var TestDepartment $task */
        $entity = $this->getEntityManager()->find(TestDepartment::class, (int)$this->getResourceId($response));
        self::assertEquals('New Entity', $entity->getName());
    }

    public function testUpdate()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $entityId = $this->getReference('entity1')->getId();
        $data = [
            'data' => [
                'type' => $entityType,
                'id'   => (string)$entityId,
                'meta' => [
                    'title' => 'Updated Entity 1'
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => (string)$entityId], $data);

        $this->assertResponseContains($data, $response);

        /** @var TestDepartment $task */
        $entity = $this->getEntityManager()->find(TestDepartment::class, $entityId);
        self::assertEquals('Updated Entity 1', $entity->getName());
    }

    public function testUpdateWithValidationConstraintViolation()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $entityId = $this->getReference('entity1')->getId();
        $data = [
            'data' => [
                'type' => $entityType,
                'id'   => (string)$entityId,
                'meta' => [
                    'title' => ''
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => (string)$entityId], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/meta/title']
            ],
            $response
        );
    }
}
