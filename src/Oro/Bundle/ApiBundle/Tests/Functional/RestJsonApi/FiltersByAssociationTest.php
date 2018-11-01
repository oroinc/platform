<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
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

    public function testEqualFilterForManyToOneAssociationBySeveralValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_4->id)>']);
        $filter = ['filter' => ['biM2O' => ['eq' => implode(',', $ids)]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testEqualFilterForManyToManyAssociationBySeveralValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_4->id)>']);
        $filter = ['filter' => ['biM2M' => ['eq' => implode(',', $ids)]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testEqualFilterForManyToOneAssociationByRange()
    {
        $ids = self::processTemplateData(['<toString(@entity2_2->id)>', '<toString(@entity2_3->id)>']);
        $filter = ['filter' => ['biM2O' => ['eq' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testEqualFilterForManyToOneAssociationByRangeWithEqualValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_1->id)>']);
        $filter = ['filter' => ['biM2O' => ['eq' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testEqualFilterForManyToManyAssociationByRange()
    {
        $ids = self::processTemplateData(['<toString(@entity2_2->id)>', '<toString(@entity2_3->id)>']);
        $filter = ['filter' => ['biM2M' => ['eq' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_3->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testEqualFilterForManyToManyAssociationByRangeWithEqualValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_2->id)>', '<toString(@entity2_2->id)>']);
        $filter = ['filter' => ['biM2M' => ['eq' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $expectedRows = [
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
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
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
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotEqualFilterForManyToManyAssociation()
    {
        $filter = ['filter' => ['biM2M' => ['neq' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotEqualFilterForOneToManyAssociation()
    {
        $filter = ['filter' => ['biO2M' => ['neq' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotEqualFilterForUnidirectionalManyToManyAssociation()
    {
        $filter = ['filter' => ['uniM2M' => ['neq' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotEqualFilterForUnidirectionalOneToManyAssociation()
    {
        $filter = ['filter' => ['uniO2M' => ['neq' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotEqualFilterForNestedManyToManyAssociation()
    {
        $filter = ['filter' => ['biM2M.biM2MOwners.biM2M' => ['neq' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotEqualFilterForManyToOneAssociationBySeveralValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_4->id)>']);
        $filter = ['filter' => ['biM2O' => ['neq' => implode(',', $ids)]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotEqualFilterForManyToManyAssociationBySeveralValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_4->id)>']);
        $filter = ['filter' => ['biM2M' => ['neq' => implode(',', $ids)]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotEqualFilterForManyToOneAssociationByRange()
    {
        $ids = self::processTemplateData(['<toString(@entity2_2->id)>', '<toString(@entity2_3->id)>']);
        $filter = ['filter' => ['biM2O' => ['neq' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotEqualFilterForManyToOneAssociationByRangeWithEqualValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_1->id)>']);
        $filter = ['filter' => ['biM2O' => ['neq' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotEqualFilterForManyToManyAssociationByRange()
    {
        $ids = self::processTemplateData(['<toString(@entity2_2->id)>', '<toString(@entity2_3->id)>']);
        $filter = ['filter' => ['biM2M' => ['neq' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotEqualFilterForManyToManyAssociationByRangeWithEqualValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_2->id)>', '<toString(@entity2_2->id)>']);
        $filter = ['filter' => ['biM2M' => ['neq' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testExistsFilterForEnum()
    {
        $filter = ['filter' => ['enumField' => ['exists' => true]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testExistsFilterForManyToOneAssociation()
    {
        $filter = ['filter' => ['biM2O' => ['exists' => true]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testExistsFilterForManyToManyAssociation()
    {
        $filter = ['filter' => ['biM2M' => ['exists' => true]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testExistsFilterForOneToManyAssociation()
    {
        $filter = ['filter' => ['biO2M' => ['exists' => true]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testExistsFilterForUnidirectionalManyToManyAssociation()
    {
        $filter = ['filter' => ['uniM2M' => ['exists' => true]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testExistsFilterForUnidirectionalOneToManyAssociation()
    {
        $filter = ['filter' => ['uniO2M' => ['exists' => true]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testExistsFilterForNestedManyToManyAssociation()
    {
        $filter = ['filter' => ['biM2M.biM2MOwners.biM2M' => ['exists' => true]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testExistsFilterForManyToOneAssociationBySeveralValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_4->id)>']);
        $filter = ['filter' => ['biM2O' => ['exists' => implode(',', $ids)]]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => sprintf('Expected boolean value. Given "%s".', implode(',', $ids)),
                'source' => ['parameter' => 'filter[biM2O]']
            ],
            $response
        );
    }

    public function testExistsFilterForManyToManyAssociationBySeveralValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_4->id)>']);
        $filter = ['filter' => ['biM2M' => ['exists' => implode(',', $ids)]]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => sprintf('Expected boolean value. Given "%s".', implode(',', $ids)),
                'source' => ['parameter' => 'filter[biM2M]']
            ],
            $response
        );
    }

    public function testExistsFilterForManyToOneAssociationByRange()
    {
        $ids = self::processTemplateData(['<toString(@entity2_2->id)>', '<toString(@entity2_3->id)>']);
        $filter = ['filter' => ['biM2O' => ['exists' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => sprintf('Expected boolean value. Given "%s..%s".', $ids[0], $ids[1]),
                'source' => ['parameter' => 'filter[biM2O]']
            ],
            $response
        );
    }

    public function testExistsFilterForManyToOneAssociationByRangeWithEqualValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_1->id)>']);
        $filter = ['filter' => ['biM2O' => ['exists' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => sprintf('Expected boolean value. Given "%s..%s".', $ids[0], $ids[1]),
                'source' => ['parameter' => 'filter[biM2O]']
            ],
            $response
        );
    }

    public function testExistsFilterForManyToManyAssociationByRange()
    {
        $ids = self::processTemplateData(['<toString(@entity2_2->id)>', '<toString(@entity2_3->id)>']);
        $filter = ['filter' => ['biM2M' => ['exists' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => sprintf('Expected boolean value. Given "%s..%s".', $ids[0], $ids[1]),
                'source' => ['parameter' => 'filter[biM2M]']
            ],
            $response
        );
    }

    public function testExistsFilterForManyToManyAssociationByRangeWithEqualValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_2->id)>', '<toString(@entity2_2->id)>']);
        $filter = ['filter' => ['biM2M' => ['exists' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => sprintf('Expected boolean value. Given "%s..%s".', $ids[0], $ids[1]),
                'source' => ['parameter' => 'filter[biM2M]']
            ],
            $response
        );
    }

    public function testNotExistsFilterForEnum()
    {
        $filter = ['filter' => ['enumField' => ['exists' => false]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotExistsFilterForManyToOneAssociation()
    {
        $filter = ['filter' => ['biM2O' => ['exists' => false]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotExistsFilterForManyToManyAssociation()
    {
        $filter = ['filter' => ['biM2M' => ['exists' => false]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotExistsFilterForOneToManyAssociation()
    {
        $filter = ['filter' => ['biO2M' => ['exists' => false]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotExistsFilterForUnidirectionalForManyToManyAssociation()
    {
        $filter = ['filter' => ['uniM2M' => ['exists' => false]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotExistsFilterForUnidirectionalOneToManyAssociation()
    {
        $filter = ['filter' => ['uniO2M' => ['exists' => false]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotExistsFilterForNestedManyToManyAssociation()
    {
        $filter = ['filter' => ['biM2M.biM2MOwners.biM2M' => ['exists' => false]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNeqOrNullFilterForEnum()
    {
        $filter = ['filter' => ['enumField' => ['neq_or_null' => '<toString(@enum1_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNeqOrNullFilterForManyToOneAssociation()
    {
        $filter = ['filter' => ['biM2O' => ['neq_or_null' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNeqOrNullFilterForManyToManyAssociation()
    {
        $filter = ['filter' => ['biM2M' => ['neq_or_null' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNeqOrNullFilterForOneToManyAssociation()
    {
        $filter = ['filter' => ['biO2M' => ['neq_or_null' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNeqOrNullFilterForUnidirectionalManyToManyAssociation()
    {
        $filter = ['filter' => ['uniM2M' => ['neq_or_null' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNeqOrNullFilterForUnidirectionalOneToManyAssociation()
    {
        $filter = ['filter' => ['uniO2M' => ['neq_or_null' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNeqOrNullFilterForNestedManyToManyAssociation()
    {
        $filter = ['filter' => ['biM2M.biM2MOwners.biM2M' => ['neq_or_null' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNeqOrNullFilterForManyToOneAssociationBySeveralValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_4->id)>']);
        $filter = ['filter' => ['biM2O' => ['neq_or_null' => implode(',', $ids)]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNeqOrNullFilterForManyToManyAssociationBySeveralValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_4->id)>']);
        $filter = ['filter' => ['biM2M' => ['neq_or_null' => implode(',', $ids)]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNeqOrNullFilterForManyToOneAssociationByRange()
    {
        $ids = self::processTemplateData(['<toString(@entity2_2->id)>', '<toString(@entity2_3->id)>']);
        $filter = ['filter' => ['biM2O' => ['neq_or_null' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter);
        // uncomment after the fix BAP-17436
        //$response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        // uncomment after the fix BAP-17436
        //self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNeqOrNullFilterForManyToOneAssociationByRangeWithEqualValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_1->id)>']);
        $filter = ['filter' => ['biM2O' => ['neq_or_null' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter);
        // uncomment after the fix BAP-17436
        //$response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        // uncomment after the fix BAP-17436
        //self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNeqOrNullFilterForManyToManyAssociationByRange()
    {
        $ids = self::processTemplateData(['<toString(@entity2_2->id)>', '<toString(@entity2_3->id)>']);
        $filter = ['filter' => ['biM2M' => ['neq_or_null' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNeqOrNullFilterForManyToManyAssociationByRangeWithEqualValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_2->id)>', '<toString(@entity2_2->id)>']);
        $filter = ['filter' => ['biM2M' => ['neq_or_null' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testContainsFilterForEnum()
    {
        $filter = ['filter' => ['enumField' => ['contains' => '<toString(@enum1_1->id)>']]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The operator "contains" is not supported.',
                'source' => ['parameter' => 'filter[enumField]']
            ],
            $response
        );
    }

    public function testContainsFilterForManyToOneAssociation()
    {
        $filter = ['filter' => ['biM2O' => ['contains' => '<toString(@entity2_1->id)>']]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The operator "contains" is not supported.',
                'source' => ['parameter' => 'filter[biM2O]']
            ],
            $response
        );
    }

    public function testContainsFilterForManyToManyAssociation()
    {
        $filter = ['filter' => ['biM2M' => ['contains' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testContainsFilterForOneToManyAssociation()
    {
        $filter = ['filter' => ['biO2M' => ['contains' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testContainsFilterForUnidirectionalManyToManyAssociation()
    {
        $filter = ['filter' => ['uniM2M' => ['contains' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testContainsFilterForUnidirectionalOneToManyAssociation()
    {
        $filter = ['filter' => ['uniO2M' => ['contains' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testContainsFilterForNestedManyToManyAssociation()
    {
        $filter = ['filter' => ['biM2M.biM2MOwners.biM2M' => ['contains' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testContainsFilterForManyToOneAssociationBySeveralValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_4->id)>']);
        $filter = ['filter' => ['biM2O' => ['contains' => implode(',', $ids)]]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => sprintf('Expected integer value. Given "%s".', implode(',', $ids)),
                'source' => ['parameter' => 'filter[biM2O]']
            ],
            $response
        );
    }

    public function testContainsFilterForManyToManyAssociationBySeveralValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_2->id)>']);
        $filter = ['filter' => ['biM2M' => ['contains' => implode(',', $ids)]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testContainsFilterForManyToManyAssociationBySeveralValuesAndAssociationIsJoined()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_2->id)>']);
        $filter = [
            'filter' => [
                'biM2M'      => ['contains' => implode(',', $ids)],
                'biM2M.name' => ['neq_or_null' => 'Another']
            ]
        ];

        $expectedRows = [
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testContainsFilterForNestedManyToManyAssociationBySeveralValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_2->id)>']);
        $filter = ['filter' => ['biM2M.biM2MOwners.biM2M' => ['contains' => implode(',', $ids)]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testContainsFilterForNestedManyToManyAssociationBySeveralValuesAndAssociationIsJoined()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_2->id)>']);
        $filter = [
            'filter' => [
                'biM2M.biM2MOwners.biM2M'      => ['contains' => implode(',', $ids)],
                'biM2M.biM2MOwners.biM2M.name' => ['neq_or_null' => 'Another']
            ]
        ];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_2->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testContainsFilterForManyToOneAssociationByRange()
    {
        $ids = self::processTemplateData(['<toString(@entity2_2->id)>', '<toString(@entity2_3->id)>']);
        $filter = ['filter' => ['biM2O' => ['contains' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => sprintf('Expected integer value. Given "%s..%s".', $ids[0], $ids[1]),
                'source' => ['parameter' => 'filter[biM2O]']
            ],
            $response
        );
    }

    public function testContainsFilterForManyToManyAssociationByRange()
    {
        $ids = self::processTemplateData(['<toString(@entity2_2->id)>', '<toString(@entity2_3->id)>']);
        $filter = ['filter' => ['biM2M' => ['contains' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => sprintf('Expected integer value. Given "%s..%s".', $ids[0], $ids[1]),
                'source' => ['parameter' => 'filter[biM2M]']
            ],
            $response
        );
    }

    public function testNotContainsFilterForEnum()
    {
        $filter = ['filter' => ['enumField' => ['not_contains' => '<toString(@enum1_1->id)>']]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The operator "not_contains" is not supported.',
                'source' => ['parameter' => 'filter[enumField]']
            ],
            $response
        );
    }

    public function testNotContainsFilterForManyToOneAssociation()
    {
        $filter = ['filter' => ['biM2O' => ['not_contains' => '<toString(@entity2_1->id)>']]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The operator "not_contains" is not supported.',
                'source' => ['parameter' => 'filter[biM2O]']
            ],
            $response
        );
    }

    public function testNotContainsFilterForManyToManyAssociation()
    {
        $filter = ['filter' => ['biM2M' => ['not_contains' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotContainsFilterForOneToManyAssociation()
    {
        $filter = ['filter' => ['biO2M' => ['not_contains' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotContainsFilterForUnidirectionalManyToManyAssociation()
    {
        $filter = ['filter' => ['uniM2M' => ['not_contains' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotContainsFilterForUnidirectionalOneToManyAssociation()
    {
        $filter = ['filter' => ['uniO2M' => ['not_contains' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_2->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotContainsFilterForNestedManyToManyAssociation()
    {
        $filter = ['filter' => ['biM2M.biM2MOwners.biM2M' => ['not_contains' => '<toString(@entity2_1->id)>']]];

        $expectedRows = [
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotContainsFilterForManyToOneAssociationBySeveralValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_4->id)>']);
        $filter = ['filter' => ['biM2O' => ['not_contains' => implode(',', $ids)]]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => sprintf('Expected integer value. Given "%s".', implode(',', $ids)),
                'source' => ['parameter' => 'filter[biM2O]']
            ],
            $response
        );
    }

    public function testNotContainsFilterForManyToManyAssociationBySeveralValues()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_2->id)>']);
        $filter = ['filter' => ['biM2M' => ['not_contains' => implode(',', $ids)]]];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotContainsFilterForManyToManyAssociationBySeveralValuesAndAssociationIsJoined()
    {
        $ids = self::processTemplateData(['<toString(@entity2_1->id)>', '<toString(@entity2_2->id)>']);
        $filter = [
            'filter' => [
                'biM2M'      => ['not_contains' => implode(',', $ids)],
                'biM2M.name' => ['neq_or_null' => 'Another']
            ]
        ];

        $expectedRows = [
            ['id' => '<toString(@entity1_1->id)>'],
            ['id' => '<toString(@entity1_3->id)>'],
            ['id' => '<toString(@entity1_4->id)>'],
            ['id' => '<toString(@entity1_null->id)>']
        ];
        $this->prepareExpectedRows($expectedRows);

        $response = $this->cget(['entity' => 'testapientity1'], $filter, ['HTTP_X-Include' => 'totalCount']);

        $this->assertResponseContains(['data' => $expectedRows], $response);
        self::assertEquals(count($expectedRows), $response->headers->get('X-Include-Total-Count'));
    }

    public function testNotContainsFilterForManyToOneAssociationByRange()
    {
        $ids = self::processTemplateData(['<toString(@entity2_2->id)>', '<toString(@entity2_3->id)>']);
        $filter = ['filter' => ['biM2O' => ['not_contains' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => sprintf('Expected integer value. Given "%s..%s".', $ids[0], $ids[1]),
                'source' => ['parameter' => 'filter[biM2O]']
            ],
            $response
        );
    }

    public function testNotContainsFilterForManyToManyAssociationByRange()
    {
        $ids = self::processTemplateData(['<toString(@entity2_2->id)>', '<toString(@entity2_3->id)>']);
        $filter = ['filter' => ['biM2M' => ['not_contains' => sprintf('%s..%s', $ids[0], $ids[1])]]];

        $response = $this->cget(['entity' => 'testapientity1'], $filter, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => sprintf('Expected integer value. Given "%s..%s".', $ids[0], $ids[1]),
                'source' => ['parameter' => 'filter[biM2M]']
            ],
            $response
        );
    }
}
