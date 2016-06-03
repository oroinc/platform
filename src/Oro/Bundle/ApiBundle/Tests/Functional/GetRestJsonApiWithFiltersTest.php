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
    const TEST_EMPLOYEES_COUNT   = 30;

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
     * @param string      $className            The FQCN of an entity
     * @param integer     $expectedStatusCode   expected status code of a response
     * @param array       $params               request parameters
     * @param array       $expects              response expectation
     * @param string|null $idsReplacementMethod method to be used for correction ids
     * @param bool|null   $reverse
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
            'filter by field'                                                              => [
                'className'  => 'Oro\Bundle\UserBundle\Entity\User',
                'statusCode' => 200,
                'params'     => [
                    'filter' => [
                        'email' => 'admin@example.com'
                    ],
                    'fields' => [
                        'users' => 'phone,title,username,email,firstName,middleName,lastName,enabled,wrongFieldName'
                    ]
                ],
                'expects'    => $this->loadExpectation('output_1.yml')
            ],
            'filter by field of related entity (user.owner)'                               => [
                'className'  => 'Oro\Bundle\UserBundle\Entity\User',
                'statusCode' => 200,
                'params'     => [
                    'filter' => [
                        'owner.name' => 'Main'
                    ],
                    'fields' => [
                        'users' => 'phone,title,username,email,firstName,middleName,lastName,enabled,wrongFieldName'
                    ]
                ],
                'expects'    => $this->loadExpectation('output_1.yml')
            ],
            'filter by field of related entity with no result'                             => [
                'className'  => 'Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee',
                'statusCode' => 200,
                'params'     => [
                    'filter' => [
                        'department' => 999999999999999
                    ],
                    'page'   => [
                        'size' => 3
                    ]
                ],
                'expects'    => ['data' => []],
            ],
            'filter by wrong field name'                                                   => [
                'className'  => 'Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee',
                'statusCode' => 400,
                'params'     => [
                    'filter' => [
                        'wrongFieldName' => 'value'
                    ],
                    'page'   => [
                        'size' => 3
                    ]
                ],
                'expects'    => [
                    'errors' => [
                        [
                            'status' => '400',
                            'title'  => 'filter constraint',
                            'detail' => 'Filter "filter[wrongFieldName]" is not supported.',
                            'source' => [
                                'parameter' => 'filter[wrongFieldName]'
                            ]
                        ]
                    ]
                ],
            ],
            'filter by field of related entity (employee.department)'                      => [
                'className'    => 'Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee',
                'statusCode'   => 200,
                'params'       => [
                    'filter' => [
                        'department.name' => 'TestDepartment0'
                    ],
                    'page'   => [
                        'size' => 3
                    ]
                ],
                'expects'      => $this->loadExpectation('output_filters_1.yml'),
                'replacements' => 'replaceTestEmployeeIdsInExpectation',
                'reverse'      => false
            ],
            'filter by field of related entity (employee.department) with reverse sorting' => [
                'className'    => 'Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee',
                'statusCode'   => 200,
                'params'       => [
                    'filter' => [
                        'department.name' => 'TestDepartment2'
                    ],
                    'sort'   => '-id',
                    'page'   => [
                        'size' => 3
                    ]
                ],
                'expects'      => $this->loadExpectation('output_filters_2.yml'),
                'replacements' => 'replaceTestEmployeeIdsInExpectation',
                'reverse'      => true
            ],
        ];
    }

    /**
     * @param array $expectation
     * @param bool  $reverse
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
