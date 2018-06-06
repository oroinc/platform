<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class FiltersByAssociationTest extends RestJsonApiTestCase
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

        $response = $this->cget(['entity' => 'testapientity1'], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testEqualFilterForToOneAssociation()
    {
        $filter = ['filter' => ['biM2O' => ['eq' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testNotEqualFilterForEnum()
    {
        $filter = ['filter' => ['enumField' => ['neq' => '<toString(@enum1_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testNotEqualFilterForToOneAssociation()
    {
        $filter = ['filter' => ['biM2O' => ['neq' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testExistsFilterForEnum()
    {
        $filter = ['filter' => ['enumField' => ['exists' => true]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_3->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testExistsFilterForToOneAssociation()
    {
        $filter = ['filter' => ['biM2O' => ['exists' => true]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_3->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testNotExistsFilterForEnum()
    {
        $filter = ['filter' => ['enumField' => ['exists' => false]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testNotExistsFilterForToOneAssociation()
    {
        $filter = ['filter' => ['biM2O' => ['exists' => false]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testNeqOrNullFilterForEnum()
    {
        $filter = ['filter' => ['enumField' => ['neq_or_null' => '<toString(@enum1_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    public function testNeqOrNullFilterForToOneAssociation()
    {
        $filter = ['filter' => ['biM2O' => ['neq_or_null' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter);

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }
}
