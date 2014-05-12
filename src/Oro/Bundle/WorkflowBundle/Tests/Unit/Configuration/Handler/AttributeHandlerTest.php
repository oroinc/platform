<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\AttributeHandler;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

class AttributeHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AttributeHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->handler = new AttributeHandler();
    }

    /**
     * @param array $expected
     * @param array $input
     * @dataProvider handleDataProvider
     */
    public function testHandle(array $expected, array $input)
    {
        $this->assertEquals($expected, $this->handler->handle($input));
    }

    public function handleDataProvider()
    {
        return array(
            'simple configuration' => array(
                'expected' => array(
                    WorkflowConfiguration::NODE_ATTRIBUTES => array(
                        array(
                            'name' => 'test_attribute',
                            'property_path' => 'entity.test_attribute',
                        )
                    )
                ),
                'input' => array(
                    WorkflowConfiguration::NODE_ATTRIBUTES => array(
                        array(
                            'name' => 'test_attribute',
                            'property_path' => 'entity.test_attribute',
                        )
                    )
                ),
            ),
            'full configuration' => array(
                'expected' => array(
                    WorkflowConfiguration::NODE_ATTRIBUTES => array(
                        array(
                            'name' => 'test_attribute',
                            'label' => 'Test Attribute',
                            'type' => 'entity',
                            'entity_acl' => array(
                                'delete' => false,
                            ),
                            'property_path' => 'entity.test_attribute',
                        )
                    ),
                ),
                'input' => array(
                    WorkflowConfiguration::NODE_ATTRIBUTES => array(
                        array(
                            'name' => 'test_attribute',
                            'label' => 'Test Attribute',
                            'type' => 'entity',
                            'entity_acl' => array(
                                'delete' => false,
                            ),
                            'property_path' => 'entity.test_attribute',
                            'unknown_first' => 'first_value',
                            'unknown_second' => 'second_value',
                        )
                    ),
                ),
            ),
        );
    }

    public function testHandleEmptyConfiguration()
    {
        $configuration = array(
            WorkflowConfiguration::NODE_ATTRIBUTES => array(
                array('property_path' => 'entity.property')
            ),
        );

        $result = $this->handler->handle($configuration);

        $attributes = $result[WorkflowConfiguration::NODE_ATTRIBUTES];
        $this->assertCount(1, $attributes);
        $step = current($attributes);

        $this->assertArrayHasKey('name', $step);
        $this->assertStringStartsWith('attribute_', $step['name']);
    }
}
