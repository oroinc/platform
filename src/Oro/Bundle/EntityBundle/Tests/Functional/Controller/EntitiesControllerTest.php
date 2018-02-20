<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Controller;

use Extend\Entity\TestEntity1;
use Oro\Bundle\EntityExtendBundle\Tests\Functional\Fixture\LoadExtendedRelationsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EntitiesControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadExtendedRelationsData::class]);
    }

    /**
     * @dataProvider relationsProvider
     */
    public function testRelationAction($fieldName)
    {
        $testEntities = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(TestEntity1::class)
            ->getRepository(TestEntity1::class)
            ->findAll();
        
        /** @var TestEntity1 $testEntity */
        $testEntity = reset($testEntities);
        
        $this->client->request(
            'GET',
            $this->getUrl('oro_entity_relation', [
                'id' => $testEntity->getId(),
                'entityName' => 'Extend_Entity_TestEntity1',
                'fieldName' => $fieldName,
            ])
        );
        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($response, 200);
    }

    public function relationsProvider()
    {
        return [
            'unidirectional many-to-many'                => ['uniM2MNDTargets'],
            'bidirectional many-to-many'                 => ['biM2MTargets'],
            'bidirectional many-to-many without default' => ['biM2MNDTargets'],
            'unidirectional one-to-many'                 => ['uniO2MTargets'],
            'unidirectional one-to-many without default' => ['uniO2MNDTargets'],
            'bidirectional one-to-many'                  => ['biO2MNDTargets']
        ];
    }
}
