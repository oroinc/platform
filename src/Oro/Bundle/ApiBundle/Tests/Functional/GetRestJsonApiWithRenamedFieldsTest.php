<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

/**
 * @dbIsolation
 */
class GetRestJsonApiWithRenamedFieldsTest extends RestJsonApiTestCase
{
    const PRODUCT_ENTITY_CLASS = 'Oro\Bundle\TestFrameworkBundle\Entity\TestProduct';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures(['Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadRenamedFieldsTestData']);
    }

    /**
     * @param array $expected
     */
    protected function updateProductExpectedData(array &$expected)
    {
        foreach ($expected['data'] as &$data) {
            switch ($data['attributes']['renamedName']) {
                case 'product 1':
                    $data['id'] = (string)$this->getReference('test_product1')->getId();
                    break;
                case 'product 2':
                    $data['id'] = (string)$this->getReference('test_product2')->getId();
                    break;
            }
        }
        unset($data);
    }

    public function testFilteringByRenamedIdentityField()
    {
        $params = [
            'filter[id]' => (string)$this->getReference('test_product2')->getId()
        ];
        $expected = $this->loadExpectation('output_filter_by_renamed_field_product2.yml');

        $this->updateProductExpectedData($expected);

        $entityType = $this->getEntityType(self::PRODUCT_ENTITY_CLASS);

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityType]),
            $params
        );

        $this->assertApiResponseStatusCodeEquals($response, 200, $entityType, 'get list');
        $this->assertEquals($expected, json_decode($response->getContent(), true));
    }

    public function testFilteringByRenamedField()
    {
        $params = [
            'filter[renamedName]' => 'product 2'
        ];
        $expected = $this->loadExpectation('output_filter_by_renamed_field_product2.yml');

        $this->updateProductExpectedData($expected);

        $entityType = $this->getEntityType(self::PRODUCT_ENTITY_CLASS);

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityType]),
            $params
        );

        $this->assertApiResponseStatusCodeEquals($response, 200, $entityType, 'get list');
        $this->assertEquals($expected, json_decode($response->getContent(), true));
    }

    public function testFilteringByRenamedRelatedField()
    {
        $params = [
            'filter[productType.renamedName]' => 'type2'
        ];
        $expected = $this->loadExpectation('output_filter_by_renamed_field_product2.yml');

        $this->updateProductExpectedData($expected);

        $entityType = $this->getEntityType(self::PRODUCT_ENTITY_CLASS);

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityType]),
            $params
        );

        $this->assertApiResponseStatusCodeEquals($response, 200, $entityType, 'get list');
        $this->assertEquals($expected, json_decode($response->getContent(), true));
    }

    /**
     * @param array $params
     * @param array $expected
     *
     * @dataProvider getSortingByRenamedFieldData
     */
    public function testSortingByRenamedField($params, $expected)
    {
        $this->updateProductExpectedData($expected);

        $entityType = $this->getEntityType(self::PRODUCT_ENTITY_CLASS);

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityType]),
            $params
        );

        $this->assertApiResponseStatusCodeEquals($response, 200, $entityType, 'get list');
        $this->assertEquals($expected, json_decode($response->getContent(), true));
    }

    /**
     * @return array
     */
    public function getSortingByRenamedFieldData()
    {
        return [
            'use default sorting'                   => [
                'params'   => [],
                'expected' => $this->loadExpectation('output_sort_by_renamed_field_asc.yml')
            ],
            'sort by renamed identity field (ASC)'  => [
                'params'   => [
                    'sort' => 'id'
                ],
                'expected' => $this->loadExpectation('output_sort_by_renamed_field_asc.yml')
            ],
            'sort by renamed identity field (DESC)' => [
                'params'   => [
                    'sort' => '-id'
                ],
                'expected' => $this->loadExpectation('output_sort_by_renamed_field_desc.yml')
            ],
            'sort by renamed field (ASC)'           => [
                'params'   => [
                    'sort' => 'renamedName'
                ],
                'expected' => $this->loadExpectation('output_sort_by_renamed_field_asc.yml')
            ],
            'sort by renamed field (DESC)'          => [
                'params'   => [
                    'sort' => '-renamedName'
                ],
                'expected' => $this->loadExpectation('output_sort_by_renamed_field_desc.yml')
            ],
            'sort by renamed related field (ASC)'   => [
                'params'   => [
                    'sort' => 'productType.renamedName'
                ],
                'expected' => $this->loadExpectation('output_sort_by_renamed_field_asc.yml')
            ],
            'sort by renamed related field (DESC)'  => [
                'params'   => [
                    'sort' => '-productType.renamedName'
                ],
                'expected' => $this->loadExpectation('output_sort_by_renamed_field_desc.yml')
            ],
        ];
    }
}
