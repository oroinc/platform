<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;

class RestJsonApiFiltersByAssociationTest extends RestJsonApiTestCase
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

    public function testEqualFilterForEnum()
    {
        $filter = ['filter' => ['enumField' => ['eq' => '<toString(@enum1_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testEqualFilterForManyToOneAssociation()
    {
        $filter = ['filter' => ['biM2O' => ['eq' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testEqualFilterForManyToManyAssociation()
    {
        $filter = ['filter' => ['biM2M' => ['eq' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testEqualFilterForOneToManyAssociation()
    {
        $filter = ['filter' => ['biO2M' => ['eq' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testEqualFilterForUnidirectionalManyToManyAssociation()
    {
        $filter = ['filter' => ['uniM2M' => ['eq' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testEqualFilterForUnidirectionalOneToManyAssociation()
    {
        $filter = ['filter' => ['uniO2M' => ['eq' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testEqualFilterForNestedManyToManyAssociation()
    {
        $filter = ['filter' => ['biM2M.biM2MOwners.biM2M' => ['eq' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotEqualFilterForEnum()
    {
        $filter = ['filter' => ['enumField' => ['neq' => '<toString(@enum1_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotEqualFilterForManyToOneAssociation()
    {
        $filter = ['filter' => ['biM2O' => ['neq' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotEqualFilterForManyToManyAssociation()
    {
        $filter = ['filter' => ['biM2M' => ['neq' => '<toString(@entity2_1->id)>']]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The operator "!=" is not supported.',
                'source' => ['parameter' => 'filter[biM2M]']
            ],
            $response
        );
    }

    public function testNotEqualFilterForOneToManyAssociation()
    {
        $filter = ['filter' => ['biO2M' => ['neq' => '<toString(@entity2_1->id)>']]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The operator "!=" is not supported.',
                'source' => ['parameter' => 'filter[biO2M]']
            ],
            $response
        );
    }

    public function testNotEqualFilterForUnidirectionalManyToManyAssociation()
    {
        $filter = ['filter' => ['uniM2M' => ['neq' => '<toString(@entity2_1->id)>']]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The operator "!=" is not supported.',
                'source' => ['parameter' => 'filter[uniM2M]']
            ],
            $response
        );
    }

    public function testNotEqualFilterForUnidirectionalOneToManyAssociation()
    {
        $filter = ['filter' => ['uniO2M' => ['neq' => '<toString(@entity2_1->id)>']]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The operator "!=" is not supported.',
                'source' => ['parameter' => 'filter[uniO2M]']
            ],
            $response
        );
    }

    public function testNotEqualFilterForNestedManyToManyAssociation()
    {
        $filter = ['filter' => ['biM2M.biM2MOwners.biM2M' => ['neq' => '<toString(@entity2_1->id)>']]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The operator "!=" is not supported.',
                'source' => ['parameter' => 'filter[biM2M.biM2MOwners.biM2M]']
            ],
            $response
        );
    }
}
