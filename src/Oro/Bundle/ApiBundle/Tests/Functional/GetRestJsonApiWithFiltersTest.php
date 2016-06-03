<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee;

/**
 * @dbIsolation
 */
class GetRestJsonApiWithFiltersTest extends ApiTestCase
{
    /**
     * counter values depends on fixture, see DataFixtures\LoadTestData
     */
    const TEST_DEPARTMENTS_COUNT = 3;
    const TEST_EMPLOYEES_COUNT = 30;

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

        $this->loadFixtures(['Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadTestData']);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestType()
    {
        return new RequestType([RequestType::REST, RequestType::JSON_API]);
    }

    /**
     * @param string $className
     * @param array $params
     * @param array $expects
     *
     * @dataProvider getParamsAndExpectation
     */
    public function testGetEntity(
        $className,
        $expectedStatusCode,
        $params,
        $expects,
        $idsReplacementMethod = null,
        $reverse = false
    ) {
        $entityAlias = $this->valueNormalizer->normalizeValue(
            $className,
            DataType::ENTITY_TYPE,
            $this->getRequestType()
        );

        // test get list request
        $this->client->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityAlias]),
            $params,
            [],
            array_replace(
                $this->generateWsseAuthHeader(),
                ['CONTENT_TYPE' => 'application/vnd.api+json']
            )
        );

        $response = $this->client->getResponse();
        if ($idsReplacementMethod) {
            $expects = $this->{$idsReplacementMethod}($expects, $reverse);
        }

        //print_r(json_decode($response->getContent()));

        $this->assertApiResponseStatusCodeEquals($response, $expectedStatusCode, $entityAlias, 'get list');
        $this->assertEquals($expects, json_decode($response->getContent(), true));
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getParamsAndExpectation()
    {
        return [
            'Simple filter by username' => [
                'className' => 'Oro\Bundle\UserBundle\Entity\User',
                'statusCode' => 200,
                'params'  => [
                    'filter' => [
                        'username' => 'admin'
                    ],
                    'fields' => [
                        'users' => 'phone,title,username,email,firstName,middleName,lastName,enabled,wrongFieldName'
                    ]
                ],
                'expects' => $this->loadExpectation('output_1.yml')
            ],
            'Simple filter by email' => [
                'className' => 'Oro\Bundle\UserBundle\Entity\User',
                'statusCode' => 200,
                'params'  => [
                    'filter' => [
                        'email' => 'admin@example.com'
                    ],
                    'fields' => [
                        'users' => 'phone,title,username,email,firstName,middleName,lastName,enabled,wrongFieldName'
                    ]
                ],
                'expects' => $this->loadExpectation('output_1.yml')
            ],
            'Filter by business unit name' => [
                'className' => 'Oro\Bundle\UserBundle\Entity\User',
                'statusCode' => 200,
                'params'  => [
                    'filter' => [
                        'owner.name' => 'Main'
                    ],
                    'fields' => [
                        'users' => 'phone,title,username,email,firstName,middleName,lastName,enabled,wrongFieldName'
                    ]
                ],
                'expects' => $this->loadExpectation('output_1.yml')
            ],
            'Filter by organization name under business unit' => [
                'className' => 'Oro\Bundle\UserBundle\Entity\User',
                'statusCode' => 200,
                'params'  => [
                    'filter' => [
                        'owner.organization.name' => 'OroCRM'
                    ],
                    'fields' => [
                        'users' => 'phone,title,username,email,firstName,middleName,lastName,enabled,wrongFieldName'
                    ]
                ],
                'expects' => $this->loadExpectation('output_1.yml')
            ],
            'filter by subresource with no result' => [
                'className' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee',
                'statusCode' => 200,
                'params'  => [
                    'filter' => [
                        'department' => 999999999999999
                    ],
                    'page' => [
                        'size' => 3
                    ]
                ],
                'expects' => ['data' => []],
            ],
            'filter by subresource with wrong field name' => [
                'className' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee',
                'statusCode' => 400,
                'params'  => [
                    'filter' => [
                        'wrongFieldName' => 'value'
                    ],
                    'page' => [
                        'size' => 3
                    ]
                ],
                'expects' => [
                    'errors' => [
                        [
                            'status' => '400',
                            'title' => 'filter constraint',
                            'detail' => 'Filter "filter[wrongFieldName]" is not supported.',
                            'source' => [
                                'parameter' => 'filter[wrongFieldName]'
                            ]
                        ]
                    ]
                ],
            ],
            'filter by subresource field' => [
                'className' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee',
                'statusCode' => 200,
                'params'  => [
                    'filter' => [
                        'department.name' => 'TestDepartment0'
                    ],
                    'page' => [
                        'size' => 3
                    ]
                ],
                'expects' => $this->loadExpectation('output_filters_1.yml'),
                'replacements' => 'replaceTestEmployeeIdsInExpectation',
                'reverse' => false
            ],
            'filter by subresource field with reverse sorting' => [
                'className' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee',
                'statusCode' => 200,
                'params'  => [
                    'filter' => [
                        'department.name' => 'TestDepartment2'
                    ],
                    'sort' => '-id',
                    'page' => [
                        'size' => 3
                    ]
                ],
                'expects' => $this->loadExpectation('output_filters_2.yml'),
                'replacements' => 'replaceTestEmployeeIdsInExpectation',
                'reverse' => true
            ],
        ];
    }

    /**
     * @param array $expectation
     *
     * @return array
     */
    protected function replaceTestEmployeeIdsInExpectation(array $expectation, $reverse = false)
    {
        foreach ($expectation['data'] as $index => $data) {
            $employeeReferenceName = 'TestEmployee';
            if ($reverse) {
                $employeeReferenceName .= (self::TEST_EMPLOYEES_COUNT - $index);
            } else {
                $employeeReferenceName .= ($index + 1);
            }

            /** @var TestEmployee $testEmployee */
            $testEmployee = $this->getReference($employeeReferenceName);
            $expectation['data'][$index]['id'] = (string) $testEmployee->getId();
            $expectation['data'][$index]['relationships']['department']['data']['id']
                = (string) $testEmployee->getDepartment()->getId();
        }

        return $expectation;
    }
}
