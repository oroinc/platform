<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\UserBundle\Entity\User;

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

        $entityType = $this->getEntityType(User::class);
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

        $entityType = $this->getEntityType(User::class);
        $response = $this->cget(
            ['entity' => $entityType],
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

        $entityType = $this->getEntityType(User::class);
        $response = $this->cget(
            ['entity' => $entityType],
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
        $entityType = $this->getEntityType(User::class);
        $response = $this->cget(
            ['entity' => $entityType],
            ['fields' => ['unknown' => 'name'], 'page' => ['size' => 1]],
            [],
            false
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => $entityType, 'id' => '1']
                ]
            ],
            $response
        );
    }

    /**
     * @param array $params
     * @param array $expects
     *
     * @dataProvider includeAndFieldsFiltersProvider
     */
    public function testIncludeAndFieldsFilters($params, $expects)
    {
        $entityType = $this->getEntityType(User::class);

        $params['page']['size'] = 1;
        $response = $this->cget(['entity' => $entityType], $params);

        $this->assertResponseContains($expects, $response);
    }

    /**
     * @return array
     */
    public function includeAndFieldsFiltersProvider()
    {
        return [
            'Filter root entity fields. Only listed should returns without any relations and inclusions' => [
                'params'  => [
                    'fields' => [
                        'users' => 'phone,title,username,email,firstName,middleName,lastName,enabled'
                    ],
                ],
                'expects' => 'include_fields_1.yml'
            ],
            'Wrong field names should be skipped' => [
                'params'  => [
                    'include' => 'wrongFieldName1,wrongFieldName2',
                    'fields' => [
                        'users' => 'phone,title,username,email,firstName,middleName,lastName,enabled,wrongFieldName'
                    ],
                ],
                'expects' => 'include_fields_1.yml'
            ],
            'Includes should not be added due they are missed in root entity fields' => [
                'params'  => [
                    'include' => 'owner,organization',
                    'fields'  => [
                        'users' => 'phone,title,username,email,firstName,middleName,lastName,enabled'
                    ],
                ],
                'expects' => 'include_fields_1.yml'
            ],
            'Included owner and filter it\'s fields (all except createdAt, updatedAt) ' => [
                'params'  => [
                    'include' => 'owner,organization',
                    'fields'  => [
                        'users' => 'phone,title,username,email,firstName,middleName,lastName,enabled,owner',
                        'businessunits' => 'name,phone,website,email,fax,organization,owner,users'
                    ],
                ],
                'expects' => 'include_fields_2.yml'
            ],
            'Owner and Roles not included, so we cannot filter their fields, only relations will be returned' => [
                'params'  => [
                    'include' => 'organization',
                    'fields'  => [
                        'users' => 'username,firstName,lastName,email,organization,owner,roles',
                        'businessunits' => 'name,phone,website,email,fax',
                        'organizations' => 'enabled',
                        'userroles' => 'name'
                    ],
                ],
                'expects' => 'include_fields_3.yml'
            ],
            'Wrong separator' => [
                'params'  => [
                    'fields' => [
                        'users' => 'phone, title, username,email,firstName,middleName.lastName,enabled'
                    ],
                ],
                'expects' => 'include_fields_4.yml'
            ],
            'Include of third level entity' => [
                'params'  => [
                    'include' => 'owner,owner.organization',
                    'fields'  => [
                        'users' => 'username,email,owner',
                        'businessunits' => 'name,organization',
                        'organizations' => 'enabled'
                    ],
                ],
                'expects' => 'include_fields_5.yml'
            ],
        ];
    }
}
