<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestPlain;

use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestPlainApiTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @dbIsolationPerTest
 */
class EnabledHateoasTest extends RestPlainApiTestCase
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

    public function testGetList()
    {
        $this->loadCustomEntities();

        $response = $this->cget(
            ['entity' => 'testapientity2'],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $this->assertResponseContains('hateoas_cget.yml', $response);
    }

    public function testGetListWhenThereAreSeveralPages()
    {
        $this->loadEntitiesForPagination();

        $response = $this->cget(
            ['entity' => 'testapientity1'],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $expectedContent = [];
        for ($i = 1; $i <= 10; $i++) {
            $expectedContent[] = [
                'id'   => sprintf('<(@entity1_%d->id)>', $i),
                'name' => sprintf('Entity 1 (%d)', $i)
            ];
        }
        $this->assertResponseContains($expectedContent, $response);
    }

    public function testGet()
    {
        $this->loadCustomEntities();

        $response = $this->get(
            ['entity' => 'testapientity1', 'id' => '<toString(@entity1_1->id)>'],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $this->assertResponseContains('hateoas_get.yml', $response);
    }

    public function testCreate()
    {
        $this->loadCustomEntities();

        $response = $this->post(
            ['entity' => 'testapientity1'],
            'hateoas_create.yml',
            ['HTTP_HATEOAS' => true]
        );

        $expectedContent = $this->loadData('hateoas_create.yml', 'responses');
        $expectedContent = self::processTemplateData(Yaml::parse($expectedContent));
        $expectedContent['id'] = $this->getResourceId($response);
        $this->assertResponseContains($expectedContent, $response);
    }

    public function testUpdate()
    {
        $this->loadCustomEntities();

        $response = $this->patch(
            ['entity' => 'testapientity1', 'id' => '<toString(@entity1_1->id)>'],
            [
                'id'   => '<(@entity1_1->id)>',
                'name' => 'Updated Name'
            ],
            ['HTTP_HATEOAS' => true]
        );

        $this->assertResponseContains('hateoas_update.yml', $response);
    }

    public function testGetSubresourceForToOneAssociation()
    {
        $this->loadCustomEntities();

        $response = $this->getSubresource(
            ['entity' => 'testapientity1', 'id' => '<toString(@entity1_1->id)>', 'association' => 'biM2O'],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $this->assertResponseContains(
            ['id' => '<(@entity2_1->id)>'],
            $response
        );
    }

    public function testGetSubresourceForToManyAssociation()
    {
        $this->loadCustomEntities();

        $response = $this->getSubresource(
            ['entity' => 'testapientity1', 'id' => '<toString(@entity1_1->id)>', 'association' => 'biM2M'],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $this->assertResponseContains(
            [
                ['id' => '<(@entity2_1->id)>'],
                ['id' => '<(@entity2_2->id)>']
            ],
            $response
        );
    }

    public function testGetSubresourceForToManyAssociationWhenThereAreSeveralPages()
    {
        $this->loadEntitiesForPagination();

        $response = $this->getSubresource(
            ['entity' => 'testapientity1', 'id' => '<toString(@entity1_1->id)>', 'association' => 'biO2M'],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $expectedContent = [];
        for ($i = 1; $i <= 10; $i++) {
            $expectedContent[] = [
                'id'   => sprintf('<(@entity2_%d->id)>', $i),
                'name' => sprintf('Entity 2 (%d)', $i)
            ];
        }
        $this->assertResponseContains($expectedContent, $response);
    }

    public function testGetRelationshipForToOneAssociation()
    {
        $this->loadCustomEntities();

        $response = $this->getRelationship(
            ['entity' => 'testapientity1', 'id' => '<toString(@entity1_1->id)>', 'association' => 'biM2O'],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $this->assertResponseContains(
            ['id' => '<(@entity2_1->id)>'],
            $response
        );
    }

    public function testGetRelationshipForToManyAssociation()
    {
        $this->loadCustomEntities();

        $response = $this->getRelationship(
            ['entity' => 'testapientity1', 'id' => '<toString(@entity1_1->id)>', 'association' => 'biM2M'],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $this->assertResponseContains(
            [
                ['id' => '<(@entity2_1->id)>'],
                ['id' => '<(@entity2_2->id)>']
            ],
            $response
        );
    }

    public function testGetRelationshipForToManyAssociationWhenThereAreSeveralPages()
    {
        $this->loadEntitiesForPagination();

        $response = $this->getRelationship(
            ['entity' => 'testapientity1', 'id' => '<toString(@entity1_1->id)>', 'association' => 'biO2M'],
            [],
            ['HTTP_HATEOAS' => true]
        );

        $expectedContent = [];
        for ($i = 1; $i <= 10; $i++) {
            $expectedContent[] = ['id' => sprintf('<(@entity2_%d->id)>', $i)];
        }
        $this->assertResponseContains($expectedContent, $response);
    }
}
