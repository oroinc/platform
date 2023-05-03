<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowReplacementType;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowDeactivationHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkflowReplacementTypeTest extends FormIntegrationTestCase
{
    /** @var WorkflowDeactivationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $helper;

    /** @var WorkflowReplacementType */
    private $formType;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(WorkflowDeactivationHelper::class);

        $this->formType = new WorkflowReplacementType($this->helper);
        parent::setUp();
    }

    public function testGetName()
    {
        $this->assertEquals(WorkflowReplacementType::NAME, $this->formType->getName());
    }

    public function testConfigureOptions()
    {
        $optionsResolver = $this->createMock(OptionsResolver::class);
        $optionsResolver->expects($this->once())
            ->method('setDefault')
            ->with('workflow', null);
        $optionsResolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with('workflow', [WorkflowDefinition::class]);

        $this->formType->configureOptions($optionsResolver);
    }

    public function testBuildView()
    {
        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName('test');

        $workflow1 = $this->getWorkflow('workflow1');
        $workflow2 = $this->getWorkflow('workflow2');

        $formView = new FormView();

        $form = $this->createMock(FormInterface::class);

        $this->helper->expects($this->once())
            ->method('getWorkflowsToDeactivation')
            ->with($workflowDefinition)
            ->willReturn(new ArrayCollection([$workflow1, $workflow2]));

        $this->formType->buildView($formView, $form, ['workflow' => $workflowDefinition]);

        $this->assertArrayHasKey('workflowsToDeactivation', $formView->vars);
        $this->assertEquals([$workflow1, $workflow2], $formView->vars['workflowsToDeactivation']);
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(bool $isValid, array $submittedData, array $expectedData)
    {
        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName('test');

        $this->helper->expects($this->once())
            ->method('getWorkflowsForManualDeactivation')
            ->with($workflowDefinition)
            ->willReturn(['workflow2' => 'workflow2', 'workflow3' => 'workflow3']);

        $form = $this->factory->create(WorkflowReplacementType::class, [], ['workflow' => $workflowDefinition]);
        $form->submit($submittedData);

        $this->assertEquals($isValid, $form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitProvider(): array
    {
        return [
            'empty form' => [
                'valid' => true,
                'submittedData' => [],
                'expectedData' => [
                    'workflowsToDeactivation' => [],
                ],
            ],
            'deactivate workflow3' => [
                'valid' => true,
                'submittedData' => [
                    'workflowsToDeactivation' => ['workflow3'],
                ],
                'expectedData' => [
                    'workflowsToDeactivation' => ['workflow3'],
                ],
            ],
            'deactivate current workflow' => [
                'valid' => false,
                'submittedData' => [
                    'workflowsToDeactivation' => ['workflow1'],
                ],
                'expectedData' => [
                    'workflowsToDeactivation' => [],
                ],
            ],
            'deactivate invalid workflow' => [
                'valid' => false,
                'submittedData' => [
                    'workflowsToDeactivation' => ['unknown_workflow'],
                ],
                'expectedData' => [
                    'workflowsToDeactivation' => [],
                ],
            ],
        ];
    }

    private function getWorkflow(string $name): Workflow
    {
        $definition = new WorkflowDefinition();
        $definition->setName($name);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $workflow->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        return $workflow;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    new OroChoiceType()
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
