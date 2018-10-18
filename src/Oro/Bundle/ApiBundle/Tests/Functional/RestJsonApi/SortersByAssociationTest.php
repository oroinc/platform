<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Extend\Entity\TestApiE1 as Entity;
use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class SortersByAssociationTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadEnumsData::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/association_filters.yml'
        ]);
    }

    /**
     * @param array  $expectedRows
     * @param string $entityType
     */
    private function prepareExpectedRows(array &$expectedRows, $entityType = 'testapientity1')
    {
        foreach ($expectedRows as &$row) {
            $row['type'] = $entityType;
        }
    }

    public function testSorterForEnum()
    {
        $expectedRows = [
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(
            ['entity' => 'testapientity1'],
            ['sort' => '-enumField,id', 'filter[id][neq]' => '<toString(@entity1_null->id)>']
        );

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testSorterForManyToOneAssociation()
    {
        $expectedRows = [
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(
            ['entity' => 'testapientity1'],
            ['sort' => '-biM2O,id', 'filter[id][neq]' => '<toString(@entity1_null->id)>']
        );

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testSorterForManyToManyAssociation()
    {
        $expectedRows = [
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_1->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(
            ['entity' => 'testapientity1'],
            ['sort' => '-biM2M,id', 'filter[id][neq]' => '<toString(@entity1_null->id)>']
        );

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testSorterForOneToManyAssociation()
    {
        $expectedRows = [
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_1->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(
            ['entity' => 'testapientity1'],
            ['sort' => '-biO2M,id', 'filter[id][neq]' => '<toString(@entity1_null->id)>']
        );

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testSorterForUnidirectionalManyToManyAssociation()
    {
        $expectedRows = [
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_1->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(
            ['entity' => 'testapientity1'],
            ['sort' => '-uniM2M,id', 'filter[id][neq]' => '<toString(@entity1_null->id)>']
        );

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testSorterForUnidirectionalOneToManyAssociation()
    {
        $expectedRows = [
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_1->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(
            ['entity' => 'testapientity1'],
            ['sort' => '-uniO2M,id', 'filter[id][neq]' => '<toString(@entity1_null->id)>']
        );

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testSorterForRenamedManyToOneAssociation()
    {
        $this->appendEntityConfig(
            Entity::class,
            [
                'fields' => [
                    'renamedAssociation' => [
                        'property_path' => 'biM2O'
                    ]
                ]
            ]
        );

        $expectedRows = [
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(
            ['entity' => 'testapientity1'],
            ['sort' => '-renamedAssociation,id', 'filter[id][neq]' => '<toString(@entity1_null->id)>']
        );

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testSorterForRenamedManyToManyAssociation()
    {
        $this->appendEntityConfig(
            Entity::class,
            [
                'fields' => [
                    'renamedAssociation' => [
                        'property_path' => 'biM2M'
                    ]
                ]
            ]
        );

        $expectedRows = [
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_1->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(
            ['entity' => 'testapientity1'],
            ['sort' => '-renamedAssociation,id', 'filter[id][neq]' => '<toString(@entity1_null->id)>']
        );

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testSorterForRenamedOneToManyAssociation()
    {
        $this->appendEntityConfig(
            Entity::class,
            [
                'fields' => [
                    'renamedAssociation' => [
                        'property_path' => 'biO2M'
                    ]
                ]
            ]
        );

        $expectedRows = [
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_1->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(
            ['entity' => 'testapientity1'],
            ['sort' => '-renamedAssociation,id', 'filter[id][neq]' => '<toString(@entity1_null->id)>']
        );

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testSorterForNestedField()
    {
        $expectedRows = [
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(
            ['entity' => 'testapientity1'],
            ['sort' => '-biM2O.name,id', 'filter[id][neq]' => '<toString(@entity1_null->id)>']
        );

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testSorterForNestedManyToOneAssociation()
    {
        $expectedRows = [
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(
            ['entity' => 'testapientity1'],
            ['sort' => '-biM2M.biM2MOwners.biM2O,id', 'filter[id][neq]' => '<toString(@entity1_null->id)>']
        );

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }
}
