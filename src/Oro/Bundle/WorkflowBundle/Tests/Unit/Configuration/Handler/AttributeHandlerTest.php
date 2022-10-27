<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\AttributeHandler;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

class AttributeHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttributeHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new AttributeHandler();
    }

    /**
     * @dataProvider handleDataProvider
     */
    public function testHandle(array $expected, array $input)
    {
        $this->assertEquals($expected, $this->handler->handle($input));
    }

    public function handleDataProvider(): array
    {
        return [
            'simple configuration' => [
                'expected' => [
                    WorkflowConfiguration::NODE_ATTRIBUTES => [
                        [
                            'name' => 'test_attribute',
                            'property_path' => 'entity.test_attribute',
                        ]
                    ]
                ],
                'input' => [
                    WorkflowConfiguration::NODE_ATTRIBUTES => [
                        [
                            'name' => 'test_attribute',
                            'property_path' => 'entity.test_attribute',
                        ]
                    ]
                ],
            ],
            'full configuration' => [
                'expected' => [
                    WorkflowConfiguration::NODE_ATTRIBUTES => [
                        [
                            'name' => 'test_attribute',
                            'label' => 'Test Attribute', //should be kept as filtering disposed to another class
                            'type' => 'entity',
                            'entity_acl' => [
                                'delete' => false,
                            ],
                            'property_path' => 'entity.test_attribute',
                        ]
                    ],
                ],
                'input' => [
                    WorkflowConfiguration::NODE_ATTRIBUTES => [
                        [
                            'name' => 'test_attribute',
                            'label' => 'Test Attribute',
                            'type' => 'entity',
                            'entity_acl' => [
                                'delete' => false,
                            ],
                            'property_path' => 'entity.test_attribute'
                        ]
                    ],
                ],
            ],
        ];
    }

    public function testHandleEmptyConfiguration()
    {
        $configuration = [
            WorkflowConfiguration::NODE_ATTRIBUTES => [
                ['property_path' => 'entity.property']
            ],
        ];

        $result = $this->handler->handle($configuration);

        $attributes = $result[WorkflowConfiguration::NODE_ATTRIBUTES];
        $this->assertCount(1, $attributes);
        $step = current($attributes);

        $this->assertArrayHasKey('name', $step);
        $this->assertStringStartsWith('attribute_', $step['name']);
    }
}
