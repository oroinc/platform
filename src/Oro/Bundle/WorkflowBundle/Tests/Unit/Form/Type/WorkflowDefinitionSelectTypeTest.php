<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\Query\Expr;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionSelectType;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

class WorkflowDefinitionSelectTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    const WORKFLOW_ENTITY_NAME = 'stdClass';

    /** @var \PHPUnit_Framework_MockObject_MockObject|WorkflowManager */
    protected $workflowManager;

    /** @var WorkflowDefinitionSelectType */
    protected $type;

    /** @var array|WorkflowDefinition[] */
    protected $definitions = [];

    protected function setUp()
    {
        parent::setUp();

        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new WorkflowDefinitionSelectType($this->workflowManager);
    }

    public function testSubmitWithWorkflowNameOption()
    {
        $workflows = $this->getWorkflows();

        /** @var Workflow $workflow */
        $workflow = array_shift($workflows);
        $definition = $workflow->getDefinition();

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with($definition->getLabel())
            ->willReturn($workflow);

        $form = $this->factory->create($this->type, null, ['workflow_name' => $definition->getLabel()]);

        $this->assertFormSubmit(
            $form,
            [
                'choices' => [$definition->getName() => $definition]
            ],
            $definition->getName(),
            $definition
        );
    }

    public function testSubmitWithEntityClassOption()
    {
        $definitions = $this->getDefinitions();

        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflowsByEntityClass')
            ->with(self::WORKFLOW_ENTITY_NAME)
            ->willReturn($this->getWorkflows());

        $form = $this->factory->create($this->type, null, ['workflow_entity_class' => self::WORKFLOW_ENTITY_NAME]);

        $this->assertFormSubmit(
            $form,
            [
                'choices' => $definitions
            ],
            'wf_100',
            $definitions['wf_100']
        );
    }

    /**
     * @param FormInterface $form
     * @param array $expectedOptions
     * @param mixed $submittedData
     * @param mixed $expectedData
     */
    protected function assertFormSubmit(FormInterface $form, array $expectedOptions, $submittedData, $expectedData)
    {
        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
            $this->assertEquals($value, $formConfig->getOption($key));
        }

        $this->assertNull($form->getData());

        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function testGetName()
    {
        $this->assertEquals(WorkflowDefinitionSelectType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('entity', $this->type->getParent());
    }

    /**
     * @dataProvider incorrectOptionsDataProvider
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Either "workflow_name" or "workflow_entity_class" must be set
     *
     * @param $options
     */
    public function testNormalizersException(array $options)
    {
        $this->factory->create($this->type, null, $options);
    }

    /**
     * @return array
     */
    public function incorrectOptionsDataProvider()
    {
        return [
            [
                []
            ],
            [
                [
                    'class' => 'OroWorkflowBundle:WorkflowStep',
                    'property' => 'label'
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $entityType = new EntityType($this->getDefinitions());

        return [new PreloadedExtension([$entityType->getName() => $entityType], [])];
    }

    /**
     * @return array|Workflow[]
     */
    protected function getWorkflows()
    {
        $definitions = $this->getDefinitions();
        $workflows = [];

        foreach ($definitions as $definition) {
            $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
                ->disableOriginalConstructor()
                ->getMock();
            $workflow->expects($this->any())->method('getDefinition')->willReturn($definition);

            $workflows[] = $workflow;
        }

        return $workflows;
    }

    /**
     * @return array|WorkflowDefinition[]
     */
    protected function getDefinitions()
    {
        if (!$this->definitions) {
            $this->definitions = [
                'wf_42' => $this->getEntity(
                    'Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition',
                    ['name' => 'wf_42', 'label' => 'label42']
                ),
                'wf_100' => $this->getEntity(
                    'Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition',
                    ['name' => 'wf_100', 'label' => 'label100']
                )
            ];
        }

        return $this->definitions;
    }
}
