<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Config\MetaPropertiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\AddMetaProperties;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class AddMetaPropertiesTest extends ConfigProcessorTestCase
{
    /** @var AddMetaProperties */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new AddMetaProperties();
    }

    public function testProcess()
    {
        $config = [
            'fields' => [
                '__prop1__' => [
                    'data_type'     => 'string',
                    'meta_property' => true
                ]
            ]
        ];
        $configExtra = new MetaPropertiesConfigExtra();
        $configExtra->addMetaProperty('prop2');
        $configExtra->addMetaProperty('prop3', 'integer');

        $this->context->setExtras([$configExtra]);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    '__prop1__' => [
                        'data_type'     => 'string',
                        'meta_property' => true
                    ],
                    '__prop2__' => [
                        'data_type'                 => 'string',
                        'meta_property'             => true,
                        'meta_property_result_name' => 'prop2'
                    ],
                    '__prop3__' => [
                        'data_type'                 => 'integer',
                        'meta_property'             => true,
                        'meta_property_result_name' => 'prop3'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }
}
