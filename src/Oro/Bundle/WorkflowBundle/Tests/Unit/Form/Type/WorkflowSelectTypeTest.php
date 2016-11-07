<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowSelectType;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class WorkflowSelectTypeTest extends FormIntegrationTestCase
{
    const TEST_ENTITY_CLASS   = 'Test\Entity\Class';
    const TEST_WORKFLOW_NAME  = 'test_workflow_name';
    const TEST_WORKFLOW_LABEL = 'Test Workflow Label';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var WorkflowSelectType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new WorkflowSelectType($this->registry);
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->registry);
        unset($this->type);
    }

    /**
     * @param array $inputOptions
     * @param array $expectedOptions
     * @dataProvider setDefaultOptionsDataProvider
     */
    public function testSetDefaultOptions(array $inputOptions, array $expectedOptions)
    {
        $testWorkflowDefinition = new WorkflowDefinition();
        $testWorkflowDefinition->setName(self::TEST_WORKFLOW_NAME)
            ->setLabel(self::TEST_WORKFLOW_LABEL);

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())
            ->method('findBy')
            ->with(array('relatedEntity' => self::TEST_ENTITY_CLASS))
            ->will($this->returnValue(array($testWorkflowDefinition)));

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with(WorkflowDefinition::class)
            ->will($this->returnValue($repository));

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
                    'config_id' => new EntityConfigId('test', self::TEST_ENTITY_CLASS),
                ),
                'expectedOptions' => array(
                    'choices' => array(self::TEST_WORKFLOW_NAME => self::TEST_WORKFLOW_LABEL),
                )
            ),
        );
    }
}
