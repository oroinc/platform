<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SearchBundle\DependencyInjection\OroSearchExtension;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Bundle\FirstEngineBundle\FirstEngineBundle;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Bundle\SecondEngineBundle\SecondEngineBundle;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Component\Config\CumulativeResourceManager;

class OroSearchExtensionTest extends ExtensionTestCase
{
    protected function setUp()
    {
        $bundle1 = new FirstEngineBundle();
        $bundle2 = new SecondEngineBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);
    }

    public function testLoad()
    {
        $this->loadExtension(new OroSearchExtension(), ['oro_search' => [
            'engine' => 'some-other-engine',
            'engine_parameters' => ['some-engine-parameters'],
            'log_queries' => true,
        ]]);

        $this->assertParametersLoaded([
            'oro_search.engine',
            'oro_search.engine_parameters',
            'oro_search.log_queries',
            'oro_search.entities_config',
            'oro_search.twig.item_container_template',
        ]);

        $this->assertEquals([
            'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Manufacturer' => [
                'alias' => 'test_entity',
                'search_template' => 'OroSearchBundle:Test:searchResult.html.twig',
                'fields' => [
                    'products' => [
                        'name' => 'products',
                        'relation_type' => 'one-to-many',
                        'relation_fields' => [
                            'name' => [
                                'name' => 'name',
                                'target_type' => 'integer',
                                'target_fields' => [],
                                'relation_fields' => [],
                            ],
                            'type' => [
                                'name' => 'type',
                                'target_type' => 'integer',
                                'target_fields' => [],
                                'relation_fields' => [],
                            ],
                        ],
                        'target_fields' => [],
                    ],
                ],
                'label' => null,
                'title_fields' => [],
                'mode' => 'normal',
            ],
        ], $this->actualParameters['oro_search.entities_config']);
    }

    public function testOrmSearchEngineLoad()
    {
        $this->loadExtension(new OroSearchExtension(), ['oro_search' => ['engine' => 'orm']]);
        $this->assertDefinitionsLoaded([
            'test_orm_service',
        ]);
    }

    public function testOtherSearchEngineLoad()
    {
        $this->loadExtension(new OroSearchExtension(), ['oro_search' => ['engine' => 'other_engine']]);
        $this->assertDefinitionsLoaded([
            'test_engine_service',
            'test_engine_first_bundle_service',
            'test_engine_second_bundle_service'
        ]);
    }
}
