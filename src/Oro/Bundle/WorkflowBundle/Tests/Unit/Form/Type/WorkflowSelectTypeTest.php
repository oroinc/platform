<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowSelectType;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class WorkflowSelectorTypeTest extends FormIntegrationTestCase
{
    const TEST_ENTITY_CLASS   = 'Test\Entity\Class';
    const TEST_WORKFLOW_NAME  = 'test_workflow_name';
    const TEST_WORKFLOW_LABEL = 'Test Workflow Label';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflowRegistry;

    /**
     * @var WorkflowSelectType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->workflowRegistry = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new WorkflowSelectType($this->workflowRegistry);
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->workflowRegistry);
        unset($this->type);
    }

    /**
     * @param array $inputOptions
     * @param array $expectedOptions
     * @dataProvider setDefaultOptionsDataProvider
     */
    public function testSetDefaultOptions(array $inputOptions, array $expectedOptions)
    {
        $entityConnector = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\EntityConnector')
            ->disableOriginalConstructor()
            ->getMock();
        $testWorkflow = new Workflow($entityConnector);
        $testWorkflow->setName(self::TEST_WORKFLOW_NAME)
            ->setLabel(self::TEST_WORKFLOW_LABEL);
        $this->workflowRegistry->expects($this->any())
            ->method('getWorkflowByEntityClass')
            ->with(self::TEST_ENTITY_CLASS)
            ->will($this->returnValue($testWorkflow));

        $form = $this->factory->create($this->type, null, $inputOptions);

        $actualOptions = $form->getConfig()->getOptions();
        foreach ($expectedOptions as $name => $expectedValue) {
            $this->assertArrayHasKey($name, $actualOptions);
            $this->assertEquals($expectedValue, $actualOptions[$name]);
        }
    }

    /**
     * @return array
     */
    public function setDefaultOptionsDataProvider()
    {
        return array(
            'no additional data' => array(
                'inputOptions' => array(),
                'expectedOptions' => array(
                    'entity_class' => null,
                    'choices' => array(),
                )
            ),
            'custom choices' => array(
                'inputOptions' => array(
                    'choices' => array('key' => 'value')
                ),
                'expectedOptions' => array(
                    'choices' => array('key' => 'value'),
                )
            ),
            'custom entity class' => array(
                'inputOptions' => array(
                    'entity_class' => self::TEST_ENTITY_CLASS,
                ),
                'expectedOptions' => array(
                    'entity_class' => self::TEST_ENTITY_CLASS,
                    'choices' => array(self::TEST_WORKFLOW_NAME => self::TEST_WORKFLOW_LABEL),
                )
            ),
            'parent configuration id' => array(
                'inputOptions' => array(
                    'config_id' => new EntityConfigId(self::TEST_ENTITY_CLASS, 'test'),
                ),
                'expectedOptions' => array(
                    'choices' => array(self::TEST_WORKFLOW_NAME => self::TEST_WORKFLOW_LABEL),
                )
            ),
        );
    }
}
