<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\EventListener;

use Extend\Entity\TestEntity1;
use Extend\Entity\TestEntity2;
use Oro\Bundle\EntityExtendBundle\Tests\Functional\Fixture\LoadExtendedRelationsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EntityRelationGridListenerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadExtendedRelationsData::class]);
    }

    /**
     * @dataProvider relationsProvider
     */
    public function testOnBuildBefore($fieldName)
    {
        $testEntities = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(TestEntity2::class)
            ->getRepository(TestEntity2::class)
            ->findAll();

        $client = $this->getClientInstance();
        $response = $client->requestGrid([
            'gridName' => 'entity-relation-grid',
            'entity-relation-grid' => [
                'class_name' => TestEntity1::class,
                'field_name' => $fieldName,
            ]
        ], []);
        
        $json = $this->getJsonResponseContent($response, 200);
        $this->assertEquals(count($testEntities), $json['options']['totalRecords']);
    }
    
    public function relationsProvider()
    {
        return [
            'unidirectional many-to-one'                 => ['uniM2OTarget'],
            'bidirectional many-to-one'                  => ['biM2OTarget'],
            'unidirectional many-to-many'                => ['uniM2MNDTargets'],
            'bidirectional many-to-many'                 => ['biM2MTargets'],
            'bidirectional many-to-many without default' => ['biM2MNDTargets'],
            'unidirectional one-to-many'                 => ['uniO2MTargets'],
            'unidirectional one-to-many without default' => ['uniO2MNDTargets'],
            'bidirectional one-to-many'                  => ['biO2MNDTargets']
        ];
    }
}
