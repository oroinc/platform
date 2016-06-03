<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\TestFrameworkBundle\Entity\TestDepartment;

/**
 * @dbIsolation
 */
class GetRestJsonApiWithTableInheritanceTest extends ApiTestCase
{
    /**
     * FQCN of the entity being used for testing.
     */
    const ENTITY_CLASS = 'Oro\Bundle\TestFrameworkBundle\Entity\TestDepartment';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            array_replace(
                $this->generateWsseAuthHeader(),
                ['CONTENT_TYPE' => 'application/vnd.api+json']
            )
        );

        parent::setUp();

        $this->loadFixtures(['Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadTableInheritanceData']);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestType()
    {
        return new RequestType([RequestType::REST, RequestType::JSON_API]);
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

        $entityAlias = $this->valueNormalizer->normalizeValue(
            self::ENTITY_CLASS,
            DataType::ENTITY_TYPE,
            $this->getRequestType()
        );

        // test get list request
        $this->client->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityAlias, 'page[size]' => 1]),
            $params,
            [],
            array_replace(
                $this->generateWsseAuthHeader(),
                ['CONTENT_TYPE' => 'application/vnd.api+json']
            )
        );

        $response = $this->client->getResponse();

        $this->assertApiResponseStatusCodeEquals($response, 200, $entityAlias, 'get list');
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
                        'testdepartments' => 'id,title,staff'
                    ],
                    'sort'   => '-id'
                ],
                'expects' => $this->loadExpectation('output_inheritance_1.yml')
            ],
            'Related entity with table inheritance (expanded)' => [
                'params'  => [
                    'include' => 'staff',
                    'fields'  => [
                        'testdepartments' => 'id,title,staff',
                        'testemployees'   => 'id,name'
                    ],
                    'sort'    => '-id'
                ],
                'expects' => $this->loadExpectation('output_inheritance_2.yml')
            ],
        ];
    }
}
