<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

class GetRestJsonApiWithIncludeFieldsTest extends RestJsonApiTestCase
{
    /**
     * FQCN of the entity being used for testing.
     */
    const ENTITY_CLASS = 'Oro\Bundle\UserBundle\Entity\User';

    /**
     * @param array $params
     * @param array $expects
     *
     * @dataProvider getParamsAndExpectation
     */
    public function testGetEntityWithIncludeParameter($params, $expects)
    {
        $entityType = $this->getEntityType(self::ENTITY_CLASS);

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityType, 'page[size]' => 1]),
            $params
        );

        $this->assertApiResponseStatusCodeEquals($response, 200, $entityType, 'get list');
        $this->assertEquals($expects, json_decode($response->getContent(), true));
    }

    /**
     * @return array
     */
    public function getParamsAndExpectation()
    {
        return [
            'Filter root entity fields. Only listed should returns without any relations and inclusions' => [
                'params'  => [
                    'fields' => [
                        'users' => 'phone,title,username,email,firstName,middleName,lastName,enabled'
                    ],
                ],
                'expects' => $this->loadExpectation('output_1.yml')
            ],
            'Wrong field names should be skipped' => [
                'params'  => [
                    'include' => 'wrongFieldName1,wrongFieldName2',
                    'fields' => [
                        'users' => 'phone,title,username,email,firstName,middleName,lastName,enabled,wrongFieldName'
                    ],
                ],
                'expects' => $this->loadExpectation('output_1.yml')
            ],
            'Includes should not be added due they are missed in root entity fields' => [
                'params'  => [
                    'include' => 'owner,organization',
                    'fields'  => [
                        'users' => 'phone,title,username,email,firstName,middleName,lastName,enabled'
                    ],
                ],
                'expects' => $this->loadExpectation('output_1.yml')
            ],
            'Included owner and filter it\'s fields (all except createdAt, updatedAt) ' => [
                'params'  => [
                    'include' => 'owner,organization',
                    'fields'  => [
                        'users' => 'phone,title,username,email,firstName,middleName,lastName,enabled,owner',
                        'businessunits' => 'name,phone,website,email,fax,organization,owner,users'
                    ],
                ],
                'expects' => $this->loadExpectation('output_2.yml')
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
                'expects' => $this->loadExpectation('output_3.yml')
            ],
            'Wrong separator' => [
                'params'  => [
                    'fields' => [
                        'users' => 'phone, title, username,email,firstName,middleName.lastName,enabled'
                    ],
                ],
                'expects' => $this->loadExpectation('output_4.yml')
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
                'expects' => $this->loadExpectation('output_5.yml')
            ],
        ];
    }
}
