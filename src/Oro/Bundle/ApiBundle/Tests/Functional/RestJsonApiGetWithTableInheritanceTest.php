<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;

class RestJsonApiGetWithTableInheritanceTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures(['@OroApiBundle/Tests/Functional/DataFixtures/table_inheritance.yml']);
    }

    /**
     * @param array $params
     * @param array $expects
     *
     * @dataProvider getParamsAndExpectation
     */
    public function testGetEntityWithTableInheritance($params, $expects)
    {
        /** @var TestDepartment $department */
        $department = $this->getReference('test_department');

        $expects['data'][0]['id'] = (string)$department->getId();

        $expects['data'][0]['relationships']['staff']['data'][0]['id'] =
            (string)$department->getStaff()->first()->getId();
        if (isset($expects['included'][0]['id'])) {
            $expects['included'][0]['id'] = (string)(string)$department->getStaff()->first()->getId();
        }

        $entityType = $this->getEntityType(TestDepartment::class);

        // test get list request
        $response = $this->cget(['entity' => $entityType, 'page[size]' => 1], $params);

        $this->assertEquals($expects, json_decode($response->getContent(), true));
    }

    /**
     * @return array
     */
    public function getParamsAndExpectation()
    {
        return [
            'Related entity with table inheritance'            => [
                'params'  => [
                    'fields' => [
                        'testapidepartments' => 'id,title,staff'
                    ],
                    'sort'   => '-id'
                ],
                'expects' => $this->loadExpectation('output_inheritance_1.yml')
            ],
            'Related entity with table inheritance (expanded)' => [
                'params'  => [
                    'include' => 'staff',
                    'fields'  => [
                        'testapidepartments' => 'id,title,staff',
                        'testapiemployees'   => 'id,name'
                    ],
                    'sort'    => '-id'
                ],
                'expects' => $this->loadExpectation('output_inheritance_2.yml')
            ],
        ];
    }
}
