<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\ApiFeatureTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ApiFeatureTest extends RestJsonApiTestCase
{
    use ApiFeatureTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/test_department.yml'
        ]);
    }

    public function testGetListOptionsOnEnabledFeature()
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => $this->getEntityType(TestDepartment::class)]
        );

        self::assertAllowResponseHeader($response, 'OPTIONS, GET, PATCH, POST, DELETE');
    }

    public function testTryToGetListOptionsOnDisabledFeature()
    {
        $this->disableApiFeature();
        try {
            $response = $this->options(
                $this->getListRouteName(),
                ['entity' => $this->getEntityType(TestDepartment::class)],
                [],
                false
            );
        } finally {
            $this->enableApiFeature();
        }

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testGetListOnEnabledFeature()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->cget(['entity' => $entityType]);

        $this->assertResponseContains(
            ['data' => [['type' => $entityType, 'id' => '<toString(@entity1->id)>']]],
            $response
        );
    }

    public function testTryToGetListOnDisabledFeature()
    {
        $this->disableApiFeature();
        try {
            $response = $this->cget(
                ['entity' => $this->getEntityType(TestDepartment::class)],
                [],
                [],
                false
            );
        } finally {
            $this->enableApiFeature();
        }

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testGetOnEnabledFeature()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->get(['entity' => $entityType, 'id' => '<toString(@entity1->id)>']);

        $this->assertResponseContains(
            ['data' => ['type' => $entityType, 'id' => '<toString(@entity1->id)>']],
            $response
        );
    }

    public function testTryToGetOnDisabledFeature()
    {
        $this->disableApiFeature();
        try {
            $response = $this->get(
                ['entity' => $this->getEntityType(TestDepartment::class), 'id' => '<toString(@entity1->id)>'],
                [],
                [],
                false
            );
        } finally {
            $this->enableApiFeature();
        }

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testCreateOnEnabledFeature()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $data = [
            'data' => [
                'type'       => $entityType,
                'attributes' => [
                    'title' => 'test department'
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $this->assertResponseContains($data, $response);
    }

    public function testTryToCreateOnDisabledFeature()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $this->disableApiFeature();
        try {
            $response = $this->post(
                ['entity' => $entityType],
                [
                    'data' => [
                        'type'       => $entityType,
                        'attributes' => [
                            'title' => 'test department'
                        ]
                    ]
                ],
                [],
                false
            );
        } finally {
            $this->enableApiFeature();
        }

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testUpdateOnEnabledFeature()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '<toString(@entity1->id)>',
                'attributes' => [
                    'title' => 'test department'
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => '<toString(@entity1->id)>'], $data);

        $this->assertResponseContains($data, $response);
    }

    public function testTryToUpdateOnDisabledFeature()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $this->disableApiFeature();
        try {
            $response = $this->patch(
                ['entity' => $entityType, 'id' => '<toString(@entity1->id)>'],
                [
                    'data' => [
                        'type'       => $entityType,
                        'id'         => '<toString(@entity1->id)>',
                        'attributes' => [
                            'title' => 'test department'
                        ]
                    ]
                ],
                [],
                false
            );
        } finally {
            $this->enableApiFeature();
        }

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testDeleteOnEnabledFeature()
    {
        $this->delete([
            'entity' => $this->getEntityType(TestDepartment::class),
            'id'     => '<toString(@entity1->id)>'
        ]);
    }

    public function testTryToDeleteOnDisabledFeature()
    {
        $this->disableApiFeature();
        try {
            $response = $this->delete(
                ['entity' => $this->getEntityType(TestDepartment::class), 'id' => '<toString(@entity1->id)>'],
                [],
                [],
                false
            );
        } finally {
            $this->enableApiFeature();
        }

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }
}
