<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\ApiFeatureTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class ApiFeatureTest extends RestJsonApiTestCase
{
    use ApiFeatureTrait;

    /** @var string */
    private $entityType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadOrganization::class,
            LoadBusinessUnit::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/test_department.yml'
        ]);

        $this->entityType = $this->getEntityType(TestDepartment::class);
    }

    public function testGetListOptionsOnEnabledFeature()
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => $this->entityType]
        );

        self::assertAllowResponseHeader($response, 'OPTIONS, GET, PATCH, POST, DELETE');
    }

    public function testTryToGetListOptionsOnDisabledFeature()
    {
        $this->disableApiFeature();
        try {
            $response = $this->options(
                $this->getListRouteName(),
                ['entity' => $this->entityType],
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
        $response = $this->cget(['entity' => $this->entityType]);

        $this->assertResponseContains(
            ['data' => [['type' => $this->entityType, 'id' => '<toString(@entity1->id)>']]],
            $response
        );
    }

    public function testTryToGetListOnDisabledFeature()
    {
        $this->disableApiFeature();
        try {
            $response = $this->cget(
                ['entity' => $this->entityType],
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
        $response = $this->get(['entity' => $this->entityType, 'id' => '<toString(@entity1->id)>']);

        $this->assertResponseContains(
            ['data' => ['type' => $this->entityType, 'id' => '<toString(@entity1->id)>']],
            $response
        );
    }

    public function testTryToGetOnDisabledFeature()
    {
        $this->disableApiFeature();
        try {
            $response = $this->get(
                ['entity' => $this->entityType, 'id' => '<toString(@entity1->id)>'],
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
        $data = [
            'data' => [
                'type'       => $this->entityType,
                'attributes' => [
                    'title' => 'test department'
                ]
            ]
        ];

        $response = $this->post(['entity' => $this->entityType], $data);

        $this->assertResponseContains($data, $response);
    }

    public function testTryToCreateOnDisabledFeature()
    {
        $this->disableApiFeature();
        try {
            $response = $this->post(
                ['entity' => $this->entityType],
                [
                    'data' => [
                        'type'       => $this->entityType,
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
        $data = [
            'data' => [
                'type'       => $this->entityType,
                'id'         => '<toString(@entity1->id)>',
                'attributes' => [
                    'title' => 'test department'
                ]
            ]
        ];

        $response = $this->patch(['entity' => $this->entityType, 'id' => '<toString(@entity1->id)>'], $data);

        $this->assertResponseContains($data, $response);
    }

    public function testTryToUpdateOnDisabledFeature()
    {
        $this->disableApiFeature();
        try {
            $response = $this->patch(
                ['entity' => $this->entityType, 'id' => '<toString(@entity1->id)>'],
                [
                    'data' => [
                        'type'       => $this->entityType,
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
        $this->delete(['entity' => $this->entityType, 'id' => '<toString(@entity1->id)>']);
    }

    public function testTryToDeleteOnDisabledFeature()
    {
        $this->disableApiFeature();
        try {
            $response = $this->delete(
                ['entity' => $this->entityType, 'id' => '<toString(@entity1->id)>'],
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
