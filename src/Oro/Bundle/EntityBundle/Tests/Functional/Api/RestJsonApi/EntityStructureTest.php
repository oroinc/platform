<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class EntityStructureTest extends RestJsonApiTestCase
{
    /**
     * @param string   $entityId
     * @param array    $expectedData
     * @param Response $response
     */
    private function assertEntityData($entityId, array $expectedData, Response $response)
    {
        $data = self::jsonToArray($response->getContent());
        $entityData = null;
        foreach ($data['data'] as $item) {
            if ($item['id'] === $entityId) {
                $entityData = $item;
                break;
            }
        }
        self::assertArrayContains($expectedData, $entityData, $entityId);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function getUserEntityData()
    {
        return [
            'type'       => 'entitystructures',
            'id'         => 'Oro_Bundle_UserBundle_Entity_User',
            'attributes' => [
                'label'       => 'User',
                'pluralLabel' => 'Users',
                'alias'       => 'user',
                'pluralAlias' => 'users',
                'className'   => 'Oro\Bundle\UserBundle\Entity\User',
                'icon'        => 'fa-user',
                'options'     => ['auditable' => true],
                'routes'      => ['name' => 'oro_user_index', 'view' => 'oro_user_view'],
                'fields'      => [
                    [
                        'label'             => 'Id',
                        'name'              => 'id',
                        'type'              => 'integer',
                        'relationType'      => '',
                        'relatedEntityName' => null,
                        'options'           => ['identifier' => true, 'configurable' => true]
                    ],
                    [
                        'label'             => 'First name',
                        'name'              => 'firstName',
                        'type'              => 'string',
                        'relationType'      => '',
                        'relatedEntityName' => null,
                        'options'           => ['configurable' => true, 'auditable' => true]
                    ],
                    [
                        'label'             => 'Created At',
                        'name'              => 'createdAt',
                        'type'              => 'datetime',
                        'relationType'      => '',
                        'relatedEntityName' => null,
                        'options'           => ['configurable' => true]
                    ],
                    [
                        'label'             => 'Password',
                        'name'              => 'password',
                        'type'              => 'string',
                        'relationType'      => '',
                        'relatedEntityName' => null,
                        'options'           => ['exclude' => true, 'configurable' => true]
                    ],
                    [
                        'label'             => 'Owner',
                        'name'              => 'owner',
                        'type'              => 'ref-one',
                        'relationType'      => 'manyToOne',
                        'relatedEntityName' => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
                        'options'           => ['configurable' => true]
                    ],
                    [
                        'label'             => 'Groups',
                        'name'              => 'groups',
                        'type'              => 'ref-many',
                        'relationType'      => 'manyToMany',
                        'relatedEntityName' => 'Oro\Bundle\UserBundle\Entity\Group',
                        'options'           => ['configurable' => true, 'auditable' => true]
                    ],
                    [
                        'label'             => 'Status',
                        'name'              => 'auth_status',
                        'type'              => 'enum',
                        'relationType'      => 'manyToOne',
                        'relatedEntityName' => 'Extend\Entity\EV_Auth_Status',
                        'options'           => ['virtual' => true, 'configurable' => true]
                    ],
                    [
                        'label'             => 'Business Units',
                        'name'              => 'business_units_id',
                        'type'              => 'dictionary',
                        'relationType'      => '',
                        'relatedEntityName' => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
                        'options'           => ['virtual' => true]
                    ],
                    [
                        'label'             => 'Tags',
                        'name'              => 'tag_field',
                        'type'              => 'tag',
                        'relationType'      => '',
                        'relatedEntityName' => 'Oro\Bundle\TagBundle\Entity\Tag',
                        'options'           => ['virtual' => true]
                    ],
                    [
                        'label'             => 'Tags',
                        'name'              => 'tags_virtual',
                        'type'              => 'ManyToMany',
                        'relationType'      => 'manyToMany',
                        'relatedEntityName' => 'Oro\Bundle\TagBundle\Entity\Tag',
                        'options'           => []
                    ],
                    [
                        'label'             => 'Owner (Tag)',
                        'name'              => 'Oro\Bundle\TagBundle\Entity\Tag::owner',
                        'type'              => 'ref-one',
                        'relationType'      => 'manyToOne',
                        'relatedEntityName' => 'Oro\Bundle\TagBundle\Entity\Tag',
                        'options'           => ['configurable' => true]
                    ],
                    [
                        'label'             => 'Updated by (Activity list)',
                        'name'              => 'Oro\Bundle\ActivityListBundle\Entity\ActivityList::editor',
                        'type'              => 'ref-one',
                        'relationType'      => 'manyToOne',
                        'relatedEntityName' => 'Oro\Bundle\ActivityListBundle\Entity\ActivityList',
                        'options'           => [
                            'configurable' => true
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    private function getTestEntityData()
    {
        return [
            'type'       => 'entitystructures',
            'id'         => 'Extend_Entity_TestEntity1',
            'attributes' => [
                'label'       => 'extend.entity.testentity1.entity_label',
                'pluralLabel' => 'extend.entity.testentity1.entity_plural_label',
                'alias'       => 'extendtestentity1',
                'pluralAlias' => 'extendtestentity1s',
                'className'   => 'Extend\Entity\TestEntity1',
                'icon'        => null,
                'options'     => [],
                'routes'      => [],
                'fields'      => [
                    [
                        'label'             => 'extend.entity.testentity1.name.label',
                        'name'              => 'name',
                        'type'              => 'string',
                        'relationType'      => '',
                        'relatedEntityName' => null,
                        'options'           => ['configurable' => true]
                    ],
                    [
                        'label'             => 'extend.entity.testentity1.bi_m2_m_targets.label',
                        'name'              => 'biM2MTargets',
                        'type'              => 'manyToMany',
                        'relationType'      => 'manyToMany',
                        'relatedEntityName' => 'Extend\Entity\TestEntity2',
                        'options'           => ['configurable' => true]
                    ],
                    [
                        'label'             => 'extend.entity.testentity1.bi_m2_o_target.label',
                        'name'              => 'biM2OTarget',
                        'type'              => 'manyToOne',
                        'relationType'      => 'manyToOne',
                        'relatedEntityName' => 'Extend\Entity\TestEntity2',
                        'options'           => ['configurable' => true]
                    ],
                    [
                        'label'             => 'extend.entity.testentity1.bi_o2_m_targets.label',
                        'name'              => 'biO2MTargets',
                        'type'              => 'oneToMany',
                        'relationType'      => 'oneToMany',
                        'relatedEntityName' => 'Extend\Entity\TestEntity2',
                        'options'           => ['configurable' => true]
                    ]
                ]
            ]
        ];
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'entitystructures']);

        $this->assertEntityData(
            'Oro_Bundle_UserBundle_Entity_User',
            $this->getUserEntityData(),
            $response
        );
        $this->assertEntityData(
            'Extend_Entity_TestEntity1',
            $this->getTestEntityData(),
            $response
        );
    }

    public function testGetForUser()
    {
        $response = $this->get(
            ['entity' => 'entitystructures', 'id' => 'Oro_Bundle_UserBundle_Entity_User']
        );

        $this->assertResponseContains(
            ['data' => $this->getUserEntityData()],
            $response
        );
    }

    public function testGetForTestEntity()
    {
        $response = $this->get(
            ['entity' => 'entitystructures', 'id' => 'Extend_Entity_TestEntity1']
        );

        $this->assertResponseContains(
            ['data' => $this->getTestEntityData()],
            $response
        );
    }

    public function testTryGetForNotExistingEntity()
    {
        $response = $this->get(
            ['entity' => 'entitystructures', 'id' => 'Not_Existing_Entity'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not found http exception',
                'detail' => 'Entity "Not\Existing\Entity" is not configurable.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }
}
