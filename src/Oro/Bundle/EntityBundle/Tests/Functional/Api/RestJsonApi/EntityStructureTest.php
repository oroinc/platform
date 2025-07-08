<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class EntityStructureTest extends RestJsonApiTestCase
{
    private function assertEntityData(string $entityId, array $expectedData, Response $response): void
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function getUserEntityData(): array
    {
        return [
            'type'       => 'entitystructures',
            'id'         => 'Oro_Bundle_UserBundle_Entity_User',
            'attributes' => [
                'label'       => 'User',
                'pluralLabel' => 'Users',
                'alias'       => 'user',
                'pluralAlias' => 'users',
                'className'   => User::class,
                'icon'        => 'fa-user',
                'options'     => ['auditable' => true],
                'routes'      => ['name' => 'oro_user_index', 'view' => 'oro_user_view'],
                'fields'      => [
                    [
                        'label'             => 'Id',
                        'name'              => 'id',
                        'type'              => 'integer',
                        'relationType'      => null,
                        'relatedEntityName' => null,
                        'relatedEntityType' => null,
                        'options'           => ['identifier' => true, 'configurable' => true]
                    ],
                    [
                        'label'             => 'First name',
                        'name'              => 'firstName',
                        'type'              => 'string',
                        'relationType'      => null,
                        'relatedEntityName' => null,
                        'relatedEntityType' => null,
                        'options'           => ['configurable' => true, 'auditable' => true]
                    ],
                    [
                        'label'             => 'Created At',
                        'name'              => 'createdAt',
                        'type'              => 'datetime',
                        'relationType'      => null,
                        'relatedEntityName' => null,
                        'relatedEntityType' => null,
                        'options'           => ['configurable' => true]
                    ],
                    [
                        'label'             => 'Password',
                        'name'              => 'password',
                        'type'              => 'string',
                        'relationType'      => null,
                        'relatedEntityName' => null,
                        'relatedEntityType' => null,
                        'options'           => ['exclude' => true, 'configurable' => true]
                    ],
                    [
                        'label'             => 'Owner',
                        'name'              => 'owner',
                        'type'              => 'ref-one',
                        'relationType'      => 'manyToOne',
                        'relatedEntityName' => BusinessUnit::class,
                        'relatedEntityType' => 'businessunits',
                        'options'           => ['configurable' => true]
                    ],
                    [
                        'label'             => 'Groups',
                        'name'              => 'groups',
                        'type'              => 'ref-many',
                        'relationType'      => 'manyToMany',
                        'relatedEntityName' => Group::class,
                        'relatedEntityType' => 'usergroups',
                        'options'           => ['configurable' => true, 'auditable' => true]
                    ],
                    [
                        'label'             => 'Business Units',
                        'name'              => 'business_units_id',
                        'type'              => 'dictionary',
                        'relationType'      => null,
                        'relatedEntityName' => BusinessUnit::class,
                        'relatedEntityType' => 'businessunits',
                        'options'           => ['virtual' => true]
                    ],
                    [
                        'label'             => 'Tags',
                        'name'              => 'tag_field',
                        'type'              => 'tag',
                        'relationType'      => null,
                        'relatedEntityName' => Tag::class,
                        'relatedEntityType' => 'tags',
                        'options'           => ['virtual' => true]
                    ],
                    [
                        'label'             => 'Tags',
                        'name'              => 'tags_virtual',
                        'type'              => 'ManyToMany',
                        'relationType'      => 'manyToMany',
                        'relatedEntityName' => Tag::class,
                        'relatedEntityType' => 'tags',
                        'options'           => []
                    ],
                    [
                        'label'             => 'Owner (Tag)',
                        'name'              => 'Oro\Bundle\TagBundle\Entity\Tag::owner',
                        'type'              => 'ref-one',
                        'relationType'      => 'manyToOne',
                        'relatedEntityName' => Tag::class,
                        'relatedEntityType' => 'tags',
                        'options'           => ['configurable' => true]
                    ],
                    [
                        'label'             => 'Updated by (Activity list)',
                        'name'              => 'Oro\Bundle\ActivityListBundle\Entity\ActivityList::editor',
                        'type'              => 'ref-one',
                        'relationType'      => 'manyToOne',
                        'relatedEntityName' => ActivityList::class,
                        'relatedEntityType' => 'activitylists',
                        'options'           => [
                            'configurable' => true
                        ]
                    ]
                ]
            ]
        ];
    }

    private function getTestEntityData(): array
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
                        'relationType'      => null,
                        'relatedEntityName' => null,
                        'relatedEntityType' => null,
                        'options'           => ['configurable' => true]
                    ],
                    [
                        'label'             => 'extend.entity.testentity1.bi_m2_m_targets.label',
                        'name'              => 'biM2MTargets',
                        'type'              => 'manyToMany',
                        'relationType'      => 'manyToMany',
                        'relatedEntityName' => 'Extend\Entity\TestEntity2',
                        'relatedEntityType' => 'extendtestentity2s',
                        'options'           => ['configurable' => true]
                    ],
                    [
                        'label'             => 'extend.entity.testentity1.bi_m2_o_target.label',
                        'name'              => 'biM2OTarget',
                        'type'              => 'manyToOne',
                        'relationType'      => 'manyToOne',
                        'relatedEntityName' => 'Extend\Entity\TestEntity2',
                        'relatedEntityType' => 'extendtestentity2s',
                        'options'           => ['configurable' => true]
                    ],
                    [
                        'label'             => 'extend.entity.testentity1.bi_o2_m_targets.label',
                        'name'              => 'biO2MTargets',
                        'type'              => 'oneToMany',
                        'relationType'      => 'oneToMany',
                        'relatedEntityName' => 'Extend\Entity\TestEntity2',
                        'relatedEntityType' => 'extendtestentity2s',
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

        self::assertArrayContains(
            ['data' => $this->getUserEntityData()],
            self::jsonToArray($response->getContent())
        );
    }

    public function testGetForTestEntity()
    {
        $response = $this->get(
            ['entity' => 'entitystructures', 'id' => 'Extend_Entity_TestEntity1']
        );

        self::assertArrayContains(
            ['data' => $this->getTestEntityData()],
            self::jsonToArray($response->getContent())
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
