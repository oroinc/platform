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
     * @param string|null $idsReplacementMethod method to be used for ids correction
     * @param string      $identifier           attribute name value to reach referenced object
     *
     * @dataProvider getParamsAndExpectation
     */
    public function testGetEntity(
        $className,
        $expectedStatusCode,
        $params,
        $expects,
        $idsReplacementMethod = null,
        $identifier = null
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
        if ($idsReplacementMethod && $identifier) {
            $expects = $this->{$idsReplacementMethod}($expects, $identifier);
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
            'filter by field'                                                                              => [
                'className'  => 'Oro\Bundle\UserBundle\Entity\User',
                'statusCode' => 200,
                'params'     => [
                    'filter' => [
                        'email' => 'admin@example.com'
                    ],
                    'fields' => [
                        'users' => 'phone,title,username,email,firstName,middleName,lastName,enabled'
                    ]
                ],
                'expects'    => $this->loadExpectation('output_1.yml')
            ],
            'filter by field of 2nd level related entity (user.owner)'                                     => [
                'className'  => 'Oro\Bundle\UserBundle\Entity\User',
                'statusCode' => 200,
                'params'     => [
                    'filter' => [
                        'owner.name' => 'Main'
                    ],
                    'fields' => [
                        'users' => 'phone,title,username,email,firstName,middleName,lastName,enabled'
                    ]
                ],
                'expects'    => $this->loadExpectation('output_1.yml')
            ],
            'filter by field of 3rd level related entity (user.owner.organization)'                        => [
                'className'    => 'Oro\Bundle\UserBundle\Entity\User',
                'statusCode'   => 200,
                'params'       => [
                    'filter' => [
                        'owner.owner.email' => 'TestBusinessUnit1@local.com'
                    ],
                    'fields' => [
                        'users' => 'username'
                    ],
                    'page'   => [
                        'size' => 3
                    ]
                ],
                'expects'      => $this->loadExpectation('output_filters_3.yml'),
                'replacements' => 'replaceUserIdsInExpectation',
                'identifier'   => 'username'
            ],
            'filter by field of 3rd level related entity (user.owner.organization) with 2nd level sorting' => [
                'className'    => 'Oro\Bundle\UserBundle\Entity\User',
                'statusCode'   => 200,
                'params'       => [
                    'filter' => [
                        'owner.owner.email' => 'TestBusinessUnit1@local.com'
                    ],
                    'fields' => [
                        'users' => 'username'
                    ],
                    'sort'   => 'owner.email',
                    'page'   => [
                        'size' => 6
                    ]
                ],
                'expects'      => $this->loadExpectation('output_filters_4.yml'),
                'replacements' => 'replaceUserIdsInExpectation',
                'identifier'   => 'username'
            ],
            'filter by field of 3rd level related entity (user.owner.organization) with 2nd level reverse sorting' => [
                'className'    => 'Oro\Bundle\UserBundle\Entity\User',
                'statusCode'   => 200,
                'params'       => [
                    'filter' => [
                        'owner.owner.email' => 'TestBusinessUnit1@local.com'
                    ],
                    'fields' => [
                        'users' => 'username'
                    ],
                    'sort'   => '-owner.email',
                    'page'   => [
                        'size' => 6
                    ]
                ],
                'expects'      => $this->loadExpectation('output_filters_5.yml'),
                'replacements' => 'replaceUserIdsInExpectation',
                'identifier'   => 'username'
            ],
            'filter by field of related entity with no result'                                             => [
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
            'filter by wrong field name'                                                                   => [
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
            'filter by field of related entity (employee.department)'                                      => [
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
                'identifier'   => 'name'
            ],
            'filter by field of related entity (employee.department) with reverse sorting'                 => [
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
                'identifier'   => 'name'
            ],
        ];
    }

    /**
     * @param array  $expectation
     * @param string $identifier
     *
     * @return array
     */
    protected function replaceTestEmployeeIdsInExpectation(array $expectation, $identifier)
    {
        foreach ($expectation['data'] as $index => $data) {
            $referenceName = $data['attributes'][$identifier];

            /** @var TestEmployee $referenceObject */
            $referenceObject = $this->getReference($referenceName);
            $expectation['data'][$index]['id'] = (string) $referenceObject->getId();
            $expectation['data'][$index]['relationships']['department']['data']['id']
                = (string) $referenceObject->getDepartment()->getId();
        }

        return $expectation;
    }

    /**
     * @param array  $expectation
     * @param string $identifier
     *
     * @return array
     */
    protected function replaceUserIdsInExpectation(array $expectation, $identifier)
    {
        foreach ($expectation['data'] as $index => $data) {
            $referenceName = $data['attributes'][$identifier];

            /** @var TestEmployee $testEmployee */
            $referenceObject = $this->getReference($referenceName);
            $expectation['data'][$index]['id'] = (string) $referenceObject->getId();
        }

        return $expectation;
    }
}
