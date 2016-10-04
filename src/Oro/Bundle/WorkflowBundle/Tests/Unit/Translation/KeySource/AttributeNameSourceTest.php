<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeySource;

use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Translation\KeySource\AttributeNameSource;

class AttributeNameSourceTest extends TranslationKeySourceTestCase
{
    public function testGetTemplate()
    {
        $keySource = new AttributeNameSource($this->getWorkflow('workflow'), ['attribute_name' => 'test_attribute']);

        $this->assertEquals(
            'oro.workflow.{{ workflow_name }}.attribute.{{ attribute_name }}.name',
            $keySource->getTemplate()
        );
    }

    /**
     * @dataProvider requiredKeysDataProvider
     *
     * @param Workflow $workflow
     * @param array $data
     * @param string $expectedMessage
     */
    public function testRequiredKeys(Workflow $workflow, array $data, $expectedMessage)
    {
        $this->setExpectedException('InvalidArgumentException', $expectedMessage);

        new AttributeNameSource($workflow, $data);
    }

    /**
     * @return array
     */
    public function requiredKeysDataProvider()
    {
        return array_merge(
            parent::requiredKeysDataProvider(),
            [
                'empty attribute name' => [
                    'workflow' => $this->getWorkflow('test'),
                    'data' => [],
                    'expectedMessage' => 'Expected not empty value for key "attribute_name" in data, null given'
                ]
            ]
        );
    }
}
