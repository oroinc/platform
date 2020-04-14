<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadBusinessUnitData;

class BusinessUnitTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadUser::class,
            LoadOrganization::class,
            LoadBusinessUnitData::class,
            LoadBusinessUnit::class
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'businessunits']
        );

        $this->assertResponseContains('cget_businessunits.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'businessunits', 'id' => $this->getReference('business_unit')->getId()]
        );

        $this->assertResponseContains('get_businessunit.yml', $response);
    }

    public function testCreate()
    {
        $response = $this->post(
            ['entity' => 'businessunits'],
            [
                'data' => [
                    'type' => 'businessunits',
                    'attributes' => [
                        'name' => 'test bu'
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => [
                                'type' => 'businessunits',
                                'id' =>'<toString(@business_unit->id)>'
                            ]
                        ]
                    ]
                ]
            ]
        );

        $responseContent = $this->updateResponseContent('create_businessunit.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testTryToCreateWithNewBusinessUnitInIncludes()
    {
        $response = $this->post(
            ['entity' => 'businessunits'],
            'create_businessunit_with_new_in_include.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'new included entity existence constraint',
                'detail' => 'Creation a new include entity that can lead to a circular dependency is forbidden.',
                'source' => [
                    'pointer' => '/included/0'
                ]
            ],
            $response
        );
    }

    public function testUpdate()
    {
        $updateData = [
            'data' => [
                'type' => 'businessunits',
                'id' => '<toString(@business_unit_2->id)>',
                'attributes' => [
                    'name' => 'renamed bu'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'businessunits', 'id' => '<toString(@business_unit_2->id)>'],
            $updateData
        );

        $this->assertResponseContains($updateData, $response);
    }

    public function testUpdateSetParent()
    {
        $updateData = [
            'data' => [
                'type' => 'businessunits',
                'id' => '<toString(@business_unit_2->id)>',
                'relationships' => [
                    'owner' => [
                        'data' => [
                            'type' => 'businessunits',
                            'id' =>'<toString(@business_unit->id)>'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'businessunits', 'id' => '<toString(@business_unit_2->id)>'],
            $updateData
        );

        $this->assertResponseContains($updateData, $response);
    }

    public function testTryToUpdateSetParentFromNewIncludedEntity()
    {
        $response = $this->patch(
            ['entity' => 'businessunits', 'id' => '<toString(@business_unit_2->id)>'],
            'update_businessunit_with_new_in_include.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'new included entity existence constraint',
                'detail' => 'Creation a new include entity that can lead to a circular dependency is forbidden.',
                'source' => [
                    'pointer' => '/included/0'
                ]
            ],
            $response
        );
    }

    public function testDelete()
    {
        $id = $this->getReference('business_unit_2')->getId();

        $this->delete(
            ['entity' => 'businessunits', 'id' => (string)$id]
        );

        $businessUnit = $this->getEntityManager()->find(BusinessUnit::class, $id);
        self::assertTrue(null === $businessUnit);
    }
}
