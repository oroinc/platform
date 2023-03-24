<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class GetWithIncludeFieldsTest extends RestJsonApiTestCase
{
    public function testIncludeFilterWhenItIsNotSupportedForApiResource()
    {
        $this->appendEntityConfig(
            User::class,
            [
                'disable_inclusion' => true
            ]
        );

        $response = $this->cget(['entity' => 'users'], ['include' => 'owner'], [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => ['parameter' => 'include']
            ],
            $response
        );
    }

    public function testIncludeFilterWhenItIsDisabledBecauseEntityDoesNotHaveAssociations()
    {
        $this->appendEntityConfig(
            TestEmployee::class,
            [
                'fields' => [
                    'department'   => ['exclude' => true],
                    'owner'        => ['exclude' => true],
                    'organization' => ['exclude' => true]
                ]
            ]
        );

        $entityType = $this->getEntityType(TestEmployee::class);
        $response = $this->cget(['entity' => $entityType], ['include' => 'owner'], [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => ['parameter' => 'include']
            ],
            $response
        );
    }

    public function testFieldsFilterWhenItIsNotSupportedForPrimaryApiResource()
    {
        $this->appendEntityConfig(
            User::class,
            [
                'disable_fieldset' => true
            ]
        );

        $response = $this->cget(
            ['entity' => 'users'],
            ['fields' => ['users' => 'firstName', 'businessunits' => 'name']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => ['parameter' => 'fields[users]']
            ],
            $response
        );
    }

    public function testFieldsFilterWhenItIsNotSupportedForRelatedApiResource()
    {
        $this->appendEntityConfig(
            BusinessUnit::class,
            [
                'disable_fieldset' => true
            ]
        );

        $response = $this->cget(
            ['entity' => 'users'],
            ['fields' => ['users' => 'firstName', 'businessunits' => 'name'], 'include' => 'owner'],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => ['parameter' => 'fields[businessunits]']
            ],
            $response
        );
    }

    public function testFieldsFilterForUnknownApiResource()
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['fields' => ['unknown' => 'name'], 'page' => ['size' => 1]],
            [],
            false
        );


        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'An entity type is not known.',
                'source' => ['parameter' => 'fields[unknown]']
            ],
            $response
        );
    }

    public function testFieldsFilter()
    {
        $params = [
            'page'   => ['size' => 1],
            'fields' => [
                'users' => 'username,firstName,middleName,lastName,email,enabled,owner'
            ]
        ];
        $expectedResponse = [
            'data' => [
                [
                    'type'          => 'users',
                    'id'            => '1',
                    'attributes'    => [
                        'username'   => 'admin',
                        'email'      => 'admin@example.com',
                        'firstName'  => 'John',
                        'middleName' => null,
                        'lastName'   => 'Doe',
                        'enabled'    => true
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'businessunits', 'id' => '1']
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertCount(count($expectedResponse['data'][0]['attributes']), $data['data'][0]['attributes']);
        self::assertCount(count($expectedResponse['data'][0]['relationships']), $data['data'][0]['relationships']);
        self::assertArrayNotHasKey('included', $data);
    }

    public function testFieldsFilterWithEmptyValue()
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['page' => ['size' => 1], 'fields' => ['users' => '']]
        );
        $this->assertResponseContains(['data' => [['type' => 'users', 'id' => '1']]], $response);

        $data = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('attributes', $data['data'][0]);
        self::assertArrayNotHasKey('relationships', $data['data'][0]);
        self::assertArrayNotHasKey('included', $data);
    }

    public function testFieldsFilterWhenEntityTypeIsNotSpecified()
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['page' => ['size' => 1], 'fields' => 'username'],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'An entity type is not specified.',
                'source' => ['parameter' => 'fields']
            ],
            $response
        );
    }

    public function testFieldsFilterWhenEntityTypeIsEmpty()
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['page' => ['size' => 1], 'fields' => ['' => 'username']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'An entity type is not specified.',
                'source' => ['parameter' => 'fields[]']
            ],
            $response
        );
    }

    public function testFieldsFilterWithWrongSeparators()
    {
        $params = [
            'page'   => ['size' => 1],
            'fields' => [
                'users' => 'phone, title, username,email,middleName.lastName,enabled'
            ]
        ];
        $expectedResponse = [
            'data' => [
                [
                    'type'       => 'users',
                    'id'         => '1',
                    'attributes' => [
                        'phone'   => null,
                        'email'   => 'admin@example.com',
                        'enabled' => true
                    ]
                ]
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertCount(count($expectedResponse['data'][0]['attributes']), $data['data'][0]['attributes']);
        self::assertArrayNotHasKey('relationships', $data['data'][0]);
        self::assertArrayNotHasKey('included', $data);
    }

    public function testFieldsFilterWithStartingComma()
    {
        $response = $this->cget(['entity' => 'users'], ['fields[users]' => ',firstName,lastName'], [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'Expected an array of strings. Given ",firstName,lastName".',
                'source' => ['parameter' => 'fields[users]']
            ],
            $response
        );
    }

    public function testFieldsFilterWithEndingComma()
    {
        $response = $this->cget(['entity' => 'users'], ['fields[users]' => 'firstName,lastName,'], [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'Expected an array of strings. Given "firstName,lastName,".',
                'source' => ['parameter' => 'fields[users]']
            ],
            $response
        );
    }

    public function testIncludeFilterWithEmptyValue()
    {
        $response = $this->cget(['entity' => 'users'], ['include' => ''], [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'Expected string value. Given "".',
                'source' => ['parameter' => 'include']
            ],
            $response
        );
    }

    public function testIncludeFilterWithStartingComma()
    {
        $response = $this->cget(['entity' => 'users'], ['include' => ',owner,organization'], [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'Expected an array of strings. Given ",owner,organization".',
                'source' => ['parameter' => 'include']
            ],
            $response
        );
    }

    public function testIncludeFilterWithEndingComma()
    {
        $response = $this->cget(['entity' => 'users'], ['include' => 'owner,organization,'], [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'Expected an array of strings. Given "owner,organization,".',
                'source' => ['parameter' => 'include']
            ],
            $response
        );
    }

    public function testIncludeFilterWithWrongFieldName()
    {
        $params = [
            'page'    => ['size' => 1],
            'include' => 'wrongField',
            'fields'  => [
                'users' => 'username,owner'
            ]
        ];
        $expectedResponse = [
            'data' => [
                [
                    'type'          => 'users',
                    'id'            => '1',
                    'attributes'    => [
                        'username' => 'admin'
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'businessunits', 'id' => '1']
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertCount(count($expectedResponse['data'][0]['attributes']), $data['data'][0]['attributes']);
        self::assertCount(count($expectedResponse['data'][0]['relationships']), $data['data'][0]['relationships']);
        self::assertArrayNotHasKey('included', $data);
    }

    public function testIncludeFilter()
    {
        $params = [
            'page'    => ['size' => 1],
            'include' => 'owner,organization'
        ];
        $expectedResponse = [
            'data'     => [
                [
                    'type'          => 'users',
                    'id'            => '1',
                    'relationships' => [
                        'owner'        => [
                            'data' => ['type' => 'businessunits', 'id' => '1']
                        ],
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => '1']
                        ]
                    ]
                ]
            ],
            'included' => [
                ['type' => 'businessunits', 'id' => '1'],
                ['type' => 'organizations', 'id' => '1']
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertTrue(count($data['data'][0]['attributes']) > 0);
        self::assertTrue(count($data['data'][0]['relationships']) > 0);
        self::assertCount(count($expectedResponse['included']), $data['included']);
        self::assertTrue(count($data['included'][0]['attributes']) > 0);
        self::assertTrue(count($data['included'][0]['relationships']) > 0);
        self::assertTrue(count($data['included'][1]['attributes']) > 0);
        self::assertTrue(count($data['included'][1]['relationships']) > 0);
    }

    public function testIncludeFilterForRenamedAssociation()
    {
        $this->appendEntityConfig(
            User::class,
            [
                'fields' => [
                    'renamedOwner' => [
                        'property_path' => 'owner'
                    ]
                ]
            ]
        );

        $params = [
            'page'    => ['size' => 1],
            'include' => 'renamedOwner'
        ];
        $expectedResponse = [
            'data'     => [
                [
                    'type'          => 'users',
                    'id'            => '1',
                    'relationships' => [
                        'renamedOwner' => [
                            'data' => ['type' => 'businessunits', 'id' => '1']
                        ]
                    ]
                ]
            ],
            'included' => [
                ['type' => 'businessunits', 'id' => '1']
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertTrue(count($data['data'][0]['attributes']) > 0);
        self::assertTrue(count($data['data'][0]['relationships']) > 0);
        self::assertCount(count($expectedResponse['included']), $data['included']);
        self::assertTrue(count($data['included'][0]['attributes']) > 0);
        self::assertTrue(count($data['included'][0]['relationships']) > 0);
    }

    public function testIncludeFilterWhenIncludeFieldsExistInFieldsFilter()
    {
        $params = [
            'page'    => ['size' => 1],
            'include' => 'owner,organization',
            'fields'  => [
                'users'         => 'username,owner,organization',
                'businessunits' => 'name,users',
                'organizations' => 'enabled'
            ]
        ];
        $expectedResponse = [
            'data'     => [
                [
                    'type'          => 'users',
                    'id'            => '1',
                    'attributes'    => [
                        'username' => 'admin'
                    ],
                    'relationships' => [
                        'owner'        => [
                            'data' => ['type' => 'businessunits', 'id' => '1']
                        ],
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => '1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => 'businessunits',
                    'id'            => '1',
                    'attributes'    => [
                        'name' => 'Main'
                    ],
                    'relationships' => [
                        'users' => [
                            'data' => [
                                ['type' => 'users', 'id' => '1']
                            ]
                        ]
                    ]
                ],
                [
                    'type'       => 'organizations',
                    'id'         => '1',
                    'attributes' => [
                        'enabled' => true
                    ]
                ]
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertCount(count($expectedResponse['data'][0]['attributes']), $data['data'][0]['attributes']);
        self::assertCount(count($expectedResponse['data'][0]['relationships']), $data['data'][0]['relationships']);
        self::assertCount(count($expectedResponse['included']), $data['included']);

        $firstIncludedIndex = -1;
        $secondIncludedIndex = -1;
        foreach ($expectedResponse['included'] as $index => $item) {
            if ('businessunits' === $item['type']) {
                $firstIncludedIndex = $index;
            }
            if ('organizations' === $item['type']) {
                $secondIncludedIndex = $index;
            }
        }

        self::assertCount(
            count($expectedResponse['included'][0]['attributes']),
            $data['included'][$firstIncludedIndex]['attributes']
        );
        self::assertCount(
            count($expectedResponse['included'][0]['relationships']),
            $data['included'][$firstIncludedIndex]['relationships']
        );
        self::assertCount(
            count($expectedResponse['included'][0]['attributes']),
            $data['included'][$secondIncludedIndex]['attributes']
        );
        self::assertFalse(
            isset($data['included'][$secondIncludedIndex]['relationships'])
        );
    }

    public function testIncludeFilterWhenIncludeFieldsDoNotExistInFieldsFilter()
    {
        $params = [
            'page'    => ['size' => 1],
            'include' => 'owner,organization',
            'fields'  => [
                'users' => 'username'
            ]
        ];
        $expectedResponse = [
            'data' => [
                [
                    'type'       => 'users',
                    'id'         => '1',
                    'attributes' => [
                        'username' => 'admin'
                    ]
                ]
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertCount(count($expectedResponse['data'][0]['attributes']), $data['data'][0]['attributes']);
        self::assertArrayNotHasKey('relationships', $data['data'][0]);
        self::assertArrayNotHasKey('included', $data);
    }

    public function testIncludeFilterForSecondLevelRelatedEntity()
    {
        $params = [
            'page'    => ['size' => 1],
            'include' => 'owner.organization'
        ];
        $expectedResponse = [
            'data'     => [
                [
                    'type'          => 'users',
                    'id'            => '1',
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'businessunits', 'id' => '1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => 'businessunits',
                    'id'            => '1',
                    'relationships' => [
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => '1']
                        ]
                    ]
                ],
                ['type' => 'organizations', 'id' => '1']
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertCount(count($expectedResponse['included']), $data['included']);
    }

    public function testIncludeFilterForSecondLevelRelatedEntityAndRenamedFirstLevelAssociation()
    {
        $this->appendEntityConfig(
            User::class,
            [
                'fields' => [
                    'renamedOwner' => [
                        'property_path' => 'owner'
                    ]
                ]
            ]
        );

        $params = [
            'page'    => ['size' => 1],
            'include' => 'renamedOwner.organization'
        ];
        $expectedResponse = [
            'data'     => [
                [
                    'type'          => 'users',
                    'id'            => '1',
                    'relationships' => [
                        'renamedOwner' => [
                            'data' => ['type' => 'businessunits', 'id' => '1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => 'businessunits',
                    'id'            => '1',
                    'relationships' => [
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => '1']
                        ]
                    ]
                ],
                ['type' => 'organizations', 'id' => '1']
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertCount(count($expectedResponse['included']), $data['included']);
    }

    public function testIncludeFilterForSecondLevelRelatedEntityAndRenamedSecondLevelAssociation()
    {
        $this->appendEntityConfig(
            BusinessUnit::class,
            [
                'fields' => [
                    'renamedOrganization' => [
                        'property_path' => 'organization'
                    ]
                ]
            ]
        );

        $params = [
            'page'    => ['size' => 1],
            'include' => 'owner.renamedOrganization'
        ];
        $expectedResponse = [
            'data'     => [
                [
                    'type'          => 'users',
                    'id'            => '1',
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'businessunits', 'id' => '1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => 'businessunits',
                    'id'            => '1',
                    'relationships' => [
                        'renamedOrganization' => [
                            'data' => ['type' => 'organizations', 'id' => '1']
                        ]
                    ]
                ],
                ['type' => 'organizations', 'id' => '1']
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertCount(count($expectedResponse['included']), $data['included']);
    }

    public function testIncludeFilterForSecondLevelRelatedEntityAndRenamedFirstAndSecondLevelAssociations()
    {
        $this->appendEntityConfig(
            User::class,
            [
                'fields' => [
                    'renamedOwner' => [
                        'property_path' => 'owner'
                    ]
                ]
            ]
        );
        $this->appendEntityConfig(
            BusinessUnit::class,
            [
                'fields' => [
                    'renamedOrganization' => [
                        'property_path' => 'organization'
                    ]
                ]
            ]
        );

        $params = [
            'page'    => ['size' => 1],
            'include' => 'renamedOwner.renamedOrganization'
        ];
        $expectedResponse = [
            'data'     => [
                [
                    'type'          => 'users',
                    'id'            => '1',
                    'relationships' => [
                        'renamedOwner' => [
                            'data' => ['type' => 'businessunits', 'id' => '1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => 'businessunits',
                    'id'            => '1',
                    'relationships' => [
                        'renamedOrganization' => [
                            'data' => ['type' => 'organizations', 'id' => '1']
                        ]
                    ]
                ],
                ['type' => 'organizations', 'id' => '1']
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertCount(count($expectedResponse['included']), $data['included']);
    }

    public function testIncludeFilterForSecondLevelRelatedEntityWhenIncludeFieldsExistInFieldsFilter()
    {
        $params = [
            'page'    => ['size' => 1],
            'include' => 'owner.organization',
            'fields'  => [
                'users'         => 'owner',
                'businessunits' => 'organization'
            ]
        ];
        $expectedResponse = [
            'data'     => [
                [
                    'type'          => 'users',
                    'id'            => '1',
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'businessunits', 'id' => '1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => 'businessunits',
                    'id'            => '1',
                    'relationships' => [
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => '1']
                        ]
                    ]
                ],
                ['type' => 'organizations', 'id' => '1']
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertCount(count($expectedResponse['included']), $data['included']);
    }

    public function testIncludeFilterForSecondLevelRelatedEntityWhenIncludeFieldsDoNotExistInFieldsFilter()
    {
        $params = [
            'page'    => ['size' => 1],
            'include' => 'owner.organization',
            'fields'  => [
                'users'         => 'username',
                'businessunits' => 'name'
            ]
        ];
        $expectedResponse = [
            'data' => [
                [
                    'type'       => 'users',
                    'id'         => '1',
                    'attributes' => [
                        'username' => 'admin'
                    ]
                ]
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('included', $data);
    }

    public function testIncludeFilterForSecondLevelRelatedEntityWhenSecondIncludeFieldsExistInFieldsFilter()
    {
        $params = [
            'page'    => ['size' => 1],
            'include' => 'owner.organization',
            'fields'  => [
                'users'         => 'owner',
                'businessunits' => 'name'
            ]
        ];
        $expectedResponse = [
            'data'     => [
                [
                    'type'          => 'users',
                    'id'            => '1',
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'businessunits', 'id' => '1']
                        ]
                    ]
                ]
            ],
            'included' => [
                ['type' => 'businessunits', 'id' => '1']
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertCount(count($expectedResponse['included']), $data['included']);
    }

    public function testIncludeFilterForFirstAndSecondLevelRelatedEntity()
    {
        $params = [
            'page'    => ['size' => 1],
            'include' => 'owner,owner.organization'
        ];
        $expectedResponse = [
            'data'     => [
                [
                    'type'          => 'users',
                    'id'            => '1',
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'businessunits', 'id' => '1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => 'businessunits',
                    'id'            => '1',
                    'relationships' => [
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => '1']
                        ]
                    ]
                ],
                ['type' => 'organizations', 'id' => '1']
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertCount(count($expectedResponse['included']), $data['included']);
    }

    public function testIncludeFilterForFirstAndSecondLevelRelatedEntityWhenIncludeFieldsExistInFieldsFilter()
    {
        $params = [
            'page'    => ['size' => 1],
            'include' => 'owner,owner.organization',
            'fields'  => [
                'users'         => 'owner',
                'businessunits' => 'organization'
            ]
        ];
        $expectedResponse = [
            'data'     => [
                [
                    'type'          => 'users',
                    'id'            => '1',
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'businessunits', 'id' => '1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => 'businessunits',
                    'id'            => '1',
                    'relationships' => [
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => '1']
                        ]
                    ]
                ],
                ['type' => 'organizations', 'id' => '1']
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertCount(count($expectedResponse['included']), $data['included']);
    }

    public function testIncludeFilterFoFirstAndSecondLevelRelatedEntityWhenIncludeFieldsDoNotExistInFieldsFilter()
    {
        $params = [
            'page'    => ['size' => 1],
            'include' => 'owner,owner.organization',
            'fields'  => [
                'users'         => 'username',
                'businessunits' => 'name'
            ]
        ];
        $expectedResponse = [
            'data' => [
                [
                    'type'       => 'users',
                    'id'         => '1',
                    'attributes' => [
                        'username' => 'admin'
                    ]
                ]
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('included', $data);
    }

    public function testIncludeFilterForFirstAndSecondLevelRelatedEntityWhenSecondIncludeFieldsExistInFieldsFilter()
    {
        $params = [
            'page'    => ['size' => 1],
            'include' => 'owner,owner.organization',
            'fields'  => [
                'users'         => 'owner',
                'businessunits' => 'name'
            ]
        ];
        $expectedResponse = [
            'data'     => [
                [
                    'type'          => 'users',
                    'id'            => '1',
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'businessunits', 'id' => '1']
                        ]
                    ]
                ]
            ],
            'included' => [
                ['type' => 'businessunits', 'id' => '1']
            ]
        ];

        $response = $this->cget(['entity' => 'users'], $params);
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertCount(count($expectedResponse['included']), $data['included']);
    }
}
