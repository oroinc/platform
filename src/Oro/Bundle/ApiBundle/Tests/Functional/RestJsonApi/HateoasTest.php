<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Extend\Entity\TestApiE1 as TestEntity1;
use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @dbIsolationPerTest
 */
class HateoasTest extends RestJsonApiTestCase
{
    private function loadCustomEntities()
    {
        $this->loadFixtures([
            LoadEnumsData::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/custom_entities.yml'
        ]);
    }

    private function loadEntitiesForPagination()
    {
        $this->loadFixtures([
            LoadEnumsData::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/entities_for_pagination.yml'
        ]);
    }

    private function renameTestEntity1Fields()
    {
        $this->appendEntityConfig(
            TestEntity1::class,
            [
                'fields' => [
                    'renamedName'           => ['property_path' => 'name'],
                    'renamedEnumField'      => ['property_path' => 'enumField'],
                    'renamedMultiEnumField' => ['property_path' => 'multiEnumField'],
                    'renamedUniM2O'         => ['property_path' => 'uniM2O'],
                    'renamedBiM2O'          => ['property_path' => 'biM2O'],
                    'renamedUniM2M'         => ['property_path' => 'uniM2M'],
                    'renamedDefaultUniM2M'  => ['property_path' => 'default_uniM2M'],
                    'renamedUniM2MnD'       => ['property_path' => 'uniM2MnD'],
                    'renamedBiM2M'          => ['property_path' => 'biM2M'],
                    'renamedDefaultBiM2M'   => ['property_path' => 'default_biM2M'],
                    'renamedBiM2MnD'        => ['property_path' => 'biM2MnD'],
                    'renamedUniO2M'         => ['property_path' => 'uniO2M'],
                    'renamedDefaultUniO2M'  => ['property_path' => 'default_uniO2M'],
                    'renamedUniO2MnD'       => ['property_path' => 'uniO2MnD'],
                    'renamedBiO2M'          => ['property_path' => 'biO2M'],
                    'renamedDefaultBiO2M'   => ['property_path' => 'default_biO2M'],
                    'renamedBiO2MnD'        => ['property_path' => 'biO2MnD'],
                ]
            ],
            true
        );
    }

    /**
     * @param array|string         $expectedContent
     * @param string|string[]|null $entityId
     *
     * @return array
     */
    private function getExpectedContent($expectedContent, $entityId = null): array
    {
        if (is_string($expectedContent)) {
            $expectedContent = $this->loadData($expectedContent, 'responses');
        } else {
            $expectedContent = Yaml::dump($expectedContent);
        }
        if (null === $entityId) {
            $valueMap = [];
        } else {
            $valueMap = is_array($entityId)
                ? $entityId
                : ['entityId' => $entityId];
        }
        $valueMap['baseUrl'] = $this->getApiBaseUrl();
        foreach ($valueMap as $key => $value) {
            $expectedContent = str_replace('{' . $key . '}', $value, $expectedContent);
        }
        $expectedContent = self::processTemplateData(Yaml::parse($expectedContent));

        return $expectedContent;
    }

    public function testGetList()
    {
        $this->loadCustomEntities();

        $entityId = $this->getReference('entity1_1')->getId();
        $response = $this->cget(
            ['entity' => 'testapientity1'],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $this->assertResponseContains(
            $this->getExpectedContent('hateoas_cget.yml', (string)$entityId),
            $response
        );
    }

    public function testGet()
    {
        $this->loadCustomEntities();

        $entityId = $this->getReference('entity1_1')->getId();
        $response = $this->get(
            ['entity' => 'testapientity1', 'id' => (string)$entityId],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $this->assertResponseContains(
            $this->getExpectedContent('hateoas_get.yml', (string)$entityId),
            $response
        );
    }

    public function testGetForRenamedFields()
    {
        $this->loadCustomEntities();

        $entityId = $this->getReference('entity1_1')->getId();
        $this->renameTestEntity1Fields();
        $response = $this->get(
            ['entity' => 'testapientity1', 'id' => (string)$entityId],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $this->assertResponseContains(
            $this->getExpectedContent('hateoas_get_renamed.yml', (string)$entityId),
            $response
        );
    }

    public function testGetWithIncludedEntities()
    {
        $this->loadCustomEntities();

        $entityId = $this->getReference('entity1_1')->getId();
        $entity21Id = $this->getReference('entity2_1')->getId();
        $enum1Id = $this->getReference('enum1_1')->getId();
        $response = $this->get(
            ['entity' => 'testapientity1', 'id' => (string)$entityId],
            ['include' => 'biM2O,enumField'],
            ['HTTP_HATEOAS' => true]
        );

        $this->assertResponseContains(
            $this->getExpectedContent(
                'hateoas_get_included.yml',
                [
                    'entityId'   => (string)$entityId,
                    'entity21Id' => (string)$entity21Id,
                    'enum1Id'    => (string)$enum1Id
                ]
            ),
            $response
        );
    }

    public function testCreate()
    {
        $this->loadFixtures([LoadEnumsData::class]);

        $response = $this->post(
            ['entity' => 'testapientity1'],
            'hateoas_create.yml',
            ['HTTP_HATEOAS' => true]
        );

        $entityId = $this->getResourceId($response);
        $entity21Id = self::getNewResourceIdFromIncludedSection($response, 'entity2_1');
        $entity22Id = self::getNewResourceIdFromIncludedSection($response, 'entity2_2');

        $this->assertResponseContains(
            $this->getExpectedContent(
                'hateoas_create.yml',
                [
                    'entityId'   => $entityId,
                    'entity21Id' => $entity21Id,
                    'entity22Id' => $entity22Id
                ]
            ),
            $response
        );
    }

    public function testUpdate()
    {
        $this->loadCustomEntities();

        $entityId = $this->getReference('entity1_1')->getId();
        $response = $this->patch(
            ['entity' => 'testapientity1', 'id' => (string)$entityId],
            [
                'data' => [
                    'type'       => 'testapientity1',
                    'id'         => (string)$entityId,
                    'attributes' => [
                        'name' => 'Updated Name'
                    ]
                ]
            ],
            ['HTTP_HATEOAS' => true]
        );

        $this->assertResponseContains(
            $this->getExpectedContent('hateoas_update.yml', (string)$entityId),
            $response
        );
    }

    public function testGetSubresourceForToOneAssociation()
    {
        $this->loadCustomEntities();

        $entityId = $this->getReference('entity1_1')->getId();
        $entity21Id = $this->getReference('entity2_1')->getId();
        $response = $this->getSubresource(
            ['entity' => 'testapientity1', 'id' => (string)$entityId, 'association' => 'biM2O'],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $this->assertResponseContains(
            $this->getExpectedContent(
                'hateoas_get_subresource_to_one.yml',
                [
                    'entityId'   => (string)$entityId,
                    'entity21Id' => (string)$entity21Id
                ]
            ),
            $response
        );
    }

    public function testGetSubresourceForToManyAssociation()
    {
        $this->loadCustomEntities();

        $entityId = $this->getReference('entity1_1')->getId();
        $entity21Id = $this->getReference('entity2_1')->getId();
        $entity22Id = $this->getReference('entity2_2')->getId();
        $response = $this->getSubresource(
            ['entity' => 'testapientity1', 'id' => (string)$entityId, 'association' => 'biM2M'],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $this->assertResponseContains(
            $this->getExpectedContent(
                'hateoas_get_subresource_to_many.yml',
                [
                    'entityId'   => (string)$entityId,
                    'entity21Id' => (string)$entity21Id,
                    'entity22Id' => (string)$entity22Id
                ]
            ),
            $response
        );
    }

    public function testGetRelationshipForToOneAssociation()
    {
        $this->loadCustomEntities();

        $entityId = $this->getReference('entity1_1')->getId();
        $entity21Id = $this->getReference('entity2_1')->getId();
        $response = $this->getRelationship(
            ['entity' => 'testapientity1', 'id' => (string)$entityId, 'association' => 'biM2O'],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $this->assertResponseContains(
            $this->getExpectedContent(
                'hateoas_get_relationship_to_one.yml',
                [
                    'entityId'   => (string)$entityId,
                    'entity21Id' => (string)$entity21Id
                ]
            ),
            $response
        );
    }

    public function testGetRelationshipForToManyAssociation()
    {
        $this->loadCustomEntities();

        $entityId = $this->getReference('entity1_1')->getId();
        $entity21Id = $this->getReference('entity2_1')->getId();
        $entity22Id = $this->getReference('entity2_2')->getId();
        $response = $this->getRelationship(
            ['entity' => 'testapientity1', 'id' => (string)$entityId, 'association' => 'biM2M'],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $this->assertResponseContains(
            $this->getExpectedContent(
                'hateoas_get_relationship_to_many.yml',
                [
                    'entityId'   => (string)$entityId,
                    'entity21Id' => (string)$entity21Id,
                    'entity22Id' => (string)$entity22Id
                ]
            ),
            $response
        );
    }

    public function testGetListWithPaginationLinksFirstPage()
    {
        $this->loadEntitiesForPagination();

        $response = $this->cget(
            ['entity' => 'testapientity1'],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $expectedLinks = $this->getExpectedContent([
            'links' => [
                'self' => '{baseUrl}/testapientity1',
                'next' => '{baseUrl}/testapientity1?page%5Bnumber%5D=2'
            ]
        ]);
        $this->assertResponseContains($expectedLinks, $response);
    }

    public function testGetListWithPaginationLinksSecondPage()
    {
        $this->loadEntitiesForPagination();

        $response = $this->cget(
            ['entity' => 'testapientity1'],
            ['page[number]' => 2],
            ['HTTP_HATEOAS' => true]
        );

        $expectedLinks = $this->getExpectedContent([
            'links' => [
                'self'  => '{baseUrl}/testapientity1',
                'first' => '{baseUrl}/testapientity1',
                'prev'  => '{baseUrl}/testapientity1',
                'next'  => '{baseUrl}/testapientity1?page%5Bnumber%5D=3'
            ]
        ]);
        $this->assertResponseContains($expectedLinks, $response);
    }

    public function testGetListWithPaginationLinksThirdPage()
    {
        $this->loadEntitiesForPagination();

        $response = $this->cget(
            ['entity' => 'testapientity1'],
            ['page[number]' => 3],
            ['HTTP_HATEOAS' => true]
        );

        $expectedLinks = $this->getExpectedContent([
            'links' => [
                'self'  => '{baseUrl}/testapientity1',
                'first' => '{baseUrl}/testapientity1',
                'prev'  => '{baseUrl}/testapientity1?page%5Bnumber%5D=2',
                'next'  => '{baseUrl}/testapientity1?page%5Bnumber%5D=4'
            ]
        ]);
        $this->assertResponseContains($expectedLinks, $response);
    }

    public function testGetListWithPaginationLinksLastPage()
    {
        $this->loadEntitiesForPagination();

        $response = $this->cget(
            ['entity' => 'testapientity1'],
            ['page[number]' => 4],
            ['HTTP_HATEOAS' => true]
        );

        $expectedLinks = $this->getExpectedContent([
            'links' => [
                'self'  => '{baseUrl}/testapientity1',
                'first' => '{baseUrl}/testapientity1',
                'prev'  => '{baseUrl}/testapientity1?page%5Bnumber%5D=3'
            ]
        ]);
        $this->assertResponseContains($expectedLinks, $response);
    }

    public function testGetListWithPaginationLinksWhenThereAreOtherFilters()
    {
        $this->loadEntitiesForPagination();

        $response = $this->cget(
            ['entity' => 'testapientity1'],
            ['page[number]' => 3, 'sort' => '-id', 'fields[testapientity1]' => 'name'],
            ['HTTP_HATEOAS' => true]
        );

        $expectedLinks = $this->getExpectedContent([
            'links' => [
                'self'  => '{baseUrl}/testapientity1',
                'first' => '{baseUrl}/testapientity1?fields%5Btestapientity1%5D=name&sort=-id',
                'prev'  => '{baseUrl}/testapientity1?fields%5Btestapientity1%5D=name&page%5Bnumber%5D=2&sort=-id',
                'next'  => '{baseUrl}/testapientity1?fields%5Btestapientity1%5D=name&page%5Bnumber%5D=4&sort=-id'
            ]
        ]);
        $this->assertResponseContains($expectedLinks, $response);
    }

    public function testGetWithPaginationLinksForAssociation()
    {
        $this->loadEntitiesForPagination();

        $entityId = $this->getReference('entity1_1')->getId();
        $response = $this->get(
            ['entity' => 'testapientity1', 'id' => (string)$entityId],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $baseUrl = '{baseUrl}/testapientity1/{entityId}';
        $expectedLinks = $this->getExpectedContent(
            [
                'data' => [
                    'type'          => 'testapientity1',
                    'id'            => (string)$entityId,
                    'relationships' => [
                        'biO2M' => [
                            'links' => [
                                'self'    => $baseUrl . '/relationships/biO2M',
                                'related' => $baseUrl . '/biO2M',
                                'next'    => $baseUrl . '/relationships/biO2M?page%5Bnumber%5D=2'
                            ]
                        ]
                    ]
                ]
            ],
            (string)$entityId
        );
        $this->assertResponseContains($expectedLinks, $response);
    }

    public function testGetListWithPaginationLinksForAssociation()
    {
        $this->loadEntitiesForPagination();

        $entityId = $this->getReference('entity1_1')->getId();
        $response = $this->cget(
            ['entity' => 'testapientity1'],
            ['filter[id]' => (string)$entityId],
            ['HTTP_HATEOAS' => true]
        );

        $baseUrl = '{baseUrl}/testapientity1/{entityId}';
        $expectedLinks = $this->getExpectedContent(
            [
                'data' => [
                    [
                        'type'          => 'testapientity1',
                        'id'            => (string)$entityId,
                        'relationships' => [
                            'biO2M' => [
                                'links' => [
                                    'self'    => $baseUrl . '/relationships/biO2M',
                                    'related' => $baseUrl . '/biO2M',
                                    'next'    => $baseUrl . '/relationships/biO2M?page%5Bnumber%5D=2'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            (string)$entityId
        );
        $this->assertResponseContains($expectedLinks, $response);
    }

    public function testUpdateWithPaginationLinksForAssociation()
    {
        $this->loadEntitiesForPagination();

        $entityId = $this->getReference('entity1_1')->getId();
        $response = $this->patch(
            ['entity' => 'testapientity1', 'id' => (string)$entityId],
            [
                'data' => [
                    'type'       => 'testapientity1',
                    'id'         => (string)$entityId,
                    'attributes' => [
                        'name' => 'Updated Name'
                    ]
                ]
            ],
            ['HTTP_HATEOAS' => true]
        );

        $baseUrl = '{baseUrl}/testapientity1/{entityId}';
        $expectedLinks = $this->getExpectedContent(
            [
                'data' => [
                    'type'          => 'testapientity1',
                    'id'            => (string)$entityId,
                    'attributes'    => [
                        'name' => 'Updated Name'
                    ],
                    'relationships' => [
                        'biO2M' => [
                            'links' => [
                                'self'    => $baseUrl . '/relationships/biO2M',
                                'related' => $baseUrl . '/biO2M',
                                'next'    => $baseUrl . '/relationships/biO2M?page%5Bnumber%5D=2'
                            ]
                        ]
                    ]
                ]
            ],
            (string)$entityId
        );
        $this->assertResponseContains($expectedLinks, $response);
    }

    public function testGetWithPaginationLinksForAssociationInIncludedEntity()
    {
        $this->loadEntitiesForPagination();

        $entityId = $this->getReference('entity2_1')->getId();
        $includedEntityId = $this->getReference('entity1_1')->getId();
        $response = $this->get(
            ['entity' => 'testapientity2', 'id' => (string)$entityId],
            ['include' => 'biO2MOwner'],
            ['HTTP_HATEOAS' => true]
        );

        $baseUrl = '{baseUrl}/testapientity1/{entityId}';
        $expectedLinks = $this->getExpectedContent(
            [
                'included' => [
                    [
                        'type'          => 'testapientity1',
                        'id'            => (string)$includedEntityId,
                        'relationships' => [
                            'biO2M' => [
                                'links' => [
                                    'self'    => $baseUrl . '/relationships/biO2M',
                                    'related' => $baseUrl . '/biO2M',
                                    'next'    => $baseUrl . '/relationships/biO2M?page%5Bnumber%5D=2'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            (string)$includedEntityId
        );
        $this->assertResponseContains($expectedLinks, $response);
    }

    public function testGetListWithPaginationLinksForAssociationInIncludedEntity()
    {
        $this->loadEntitiesForPagination();

        $entityId = $this->getReference('entity2_1')->getId();
        $includedEntityId = $this->getReference('entity1_1')->getId();
        $response = $this->cget(
            ['entity' => 'testapientity2'],
            ['include' => 'biO2MOwner', 'filter[id]' => (string)$entityId],
            ['HTTP_HATEOAS' => true]
        );

        $baseUrl = '{baseUrl}/testapientity1/{entityId}';
        $expectedLinks = $this->getExpectedContent(
            [
                'included' => [
                    [
                        'type'          => 'testapientity1',
                        'id'            => (string)$includedEntityId,
                        'relationships' => [
                            'biO2M' => [
                                'links' => [
                                    'self'    => $baseUrl . '/relationships/biO2M',
                                    'related' => $baseUrl . '/biO2M',
                                    'next'    => $baseUrl . '/relationships/biO2M?page%5Bnumber%5D=2'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            (string)$includedEntityId
        );
        $this->assertResponseContains($expectedLinks, $response);
    }

    public function testGetWithPaginationLinksForAssociationInEntityIncludedThroughSeveralLevels()
    {
        $this->loadEntitiesForPagination();

        $entityId = $this->getReference('entity1_1')->getId();
        $includedEntityId = $this->getReference('entity1_32')->getId();
        $response = $this->get(
            ['entity' => 'testapientity1', 'id' => (string)$entityId],
            [
                'include' => 'biO2M.biO2MnDOwner',
                'fields'  => [
                    'testapientity1' => 'biO2M,biO2MnD',
                    'testapientity2' => 'biO2MnDOwner'
                ]
            ],
            ['HTTP_HATEOAS' => true]
        );

        $baseUrl = '{baseUrl}/testapientity1/{entityId}';
        $expectedLinks = $this->getExpectedContent(
            [
                'included' => [
                    [
                        'type'          => 'testapientity1',
                        'id'            => (string)$includedEntityId,
                        'relationships' => [
                            'biO2MnD' => [
                                'links' => [
                                    'self'    => $baseUrl . '/relationships/biO2MnD',
                                    'related' => $baseUrl . '/biO2MnD',
                                    'next'    => $baseUrl . '/relationships/biO2MnD?page%5Bnumber%5D=2'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            (string)$includedEntityId
        );
        $this->assertResponseContains($expectedLinks, $response);
    }
}
