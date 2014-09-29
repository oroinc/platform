<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\DependencyInjection\Compiler;


use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\SearchConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SearchConfigurationPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $expectedFieldConfig = [
            'name' => 'organization',
            'target_type' => 'integer',
            'target_fields' => ['organization']
        ];
        $config = [
            'testClass' => [
                'fields' => [
                    [
                        'name' => 'name',
                        'target_type' => 'text',
                        'target_fields' => ['name']
                    ]
                ]
            ],
            'emptyClass' => [
                'fields' => []
            ]
        ];
        $container = new ContainerBuilder();
        $container->setParameter(SearchConfigurationPass::SEARCH_CONFIG, $config);
        $compiller = new SearchConfigurationPass();
        $compiller->process($container);
        $updatedConfig = $container->getParameter(SearchConfigurationPass::SEARCH_CONFIG);
        $this->assertEquals($expectedFieldConfig, $updatedConfig['testClass']['fields'][1]);
        $this->assertEquals($expectedFieldConfig, $updatedConfig['emptyClass']['fields'][0]);

    }
}
