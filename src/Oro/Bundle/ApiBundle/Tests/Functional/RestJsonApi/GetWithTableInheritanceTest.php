<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class GetWithTableInheritanceTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadOrganization::class,
            LoadBusinessUnit::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/table_inheritance.yml'
        ]);
    }

    /**
     * @param array        $params
     * @param array|string $expects
     *
     * @dataProvider getParamsAndExpectation
     */
    public function testGetEntityWithTableInheritance($params, $expects)
    {
        /** @var TestDepartment $department */
        $department = $this->getReference('test_department');

        $expects = $this->loadResponseData($expects);
        $expects['data'][0]['id'] = (string)$department->getId();

        $expects['data'][0]['relationships']['staff']['data'][0]['id'] =
            (string)$department->getStaff()->first()->getId();
        if (isset($expects['included'][0]['id'])) {
            $expects['included'][0]['id'] = (string)(string)$department->getStaff()->first()->getId();
        }

        $entityType = $this->getEntityType(TestDepartment::class);

        // test get list request
        $response = $this->cget(['entity' => $entityType, 'page[size]' => 1], $params);

        $this->assertResponseContains($expects, $response);
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
                'expects' => 'table_inheritance_1.yml'
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
                'expects' => 'table_inheritance_2.yml'
            ],
        ];
    }
}
