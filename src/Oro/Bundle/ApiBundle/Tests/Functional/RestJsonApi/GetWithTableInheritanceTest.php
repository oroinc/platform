<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class GetWithTableInheritanceTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/table_inheritance.yml'
        ]);
    }

    /**
     * @dataProvider getParamsAndExpectationDataProvider
     */
    public function testGetEntityWithTableInheritance(array $params, array|string $expects)
    {
        /** @var TestDepartment $department */
        $department = $this->getReference('test_department');

        $expects = $this->getResponseData($expects);
        $expects['data'][0]['id'] = (string)$department->getId();

        $expects['data'][0]['relationships']['staff']['data'][0]['id'] =
            (string)$department->getStaff()->first()->getId();
        if (isset($expects['included'][0]['id'])) {
            $expects['included'][0]['id'] = (string)$department->getStaff()->first()->getId();
        }

        $entityType = $this->getEntityType(TestDepartment::class);

        // test get list request
        $response = $this->cget(['entity' => $entityType, 'page[size]' => 1], $params);

        $this->assertResponseContains($expects, $response);
        $responseContent = self::jsonToArray($response->getContent());
        if (isset($responseContent['included'])) {
            foreach ($responseContent['included'] as $key => $item) {
                self::assertArrayNotHasKey('meta', $item, sprintf('included[%s]', $key));
            }
        }
    }

    public function getParamsAndExpectationDataProvider(): array
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
            ]
        ];
    }
}
