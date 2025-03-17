<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionSelectType;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class WorkflowDefinitionSelectTypeTest extends FormIntegrationTestCase
{
    private const WORKFLOW_ENTITY_NAME = 'stdClass';

    private WorkflowRegistry&MockObject $workflowRegistry;
    private TranslatorInterface&MockObject $translator;
    private WorkflowDefinitionSelectType $type;

    /** @var WorkflowDefinition[] */
    private array $definitions = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->type = new WorkflowDefinitionSelectType($this->workflowRegistry, $this->translator);

        parent::setUp();
    }

    public function testSubmitWithWorkflowNameOption(): void
    {
        $workflows = $this->getWorkflows();

        /** @var Workflow $workflow */
        $workflow = array_shift($workflows);
        $definition = $workflow->getDefinition();

        $this->workflowRegistry->expects(self::once())
            ->method('getWorkflow')
            ->with($definition->getLabel())
            ->willReturn($workflow);

        $form = $this->factory->create(
            WorkflowDefinitionSelectType::class,
            null,
            ['workflow_name' => $definition->getLabel()]
        );

        $this->assertFormSubmit(
            $form,
            [
                'choices' => [$definition->getName() => $definition]
            ],
            $definition->getName(),
            $definition
        );
    }

    public function testSubmitWithEntityClassOption(): void
    {
        $definitions = $this->getDefinitions();

        $this->workflowRegistry->expects(self::once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with(self::WORKFLOW_ENTITY_NAME)
            ->willReturn(new ArrayCollection($this->getWorkflows()));

        $form = $this->factory->create(
            WorkflowDefinitionSelectType::class,
            null,
            ['workflow_entity_class' => self::WORKFLOW_ENTITY_NAME]
        );

        $this->assertFormSubmit(
            $form,
            [
                'choices' => $definitions
            ],
            'wf_100',
            $definitions['wf_100']
        );
    }

    private function assertFormSubmit(
        FormInterface $form,
        array $expectedOptions,
        mixed $submittedData,
        mixed $expectedData
    ): void {
        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            self::assertTrue($formConfig->hasOption($key));
            self::assertEquals($value, $formConfig->getOption($key));
        }

        self::assertNull($form->getData());

        $form->submit($submittedData);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($expectedData, $form->getData());
    }

    public function testGetParent(): void
    {
        self::assertEquals(EntityType::class, $this->type->getParent());
    }

    /**
     * @dataProvider incorrectOptionsDataProvider
     */
    public function testNormalizersException(array $options): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Either "workflow_name" or "workflow_entity_class" must be set');

        $this->factory->create(WorkflowDefinitionSelectType::class, null, $options);
    }

    public function incorrectOptionsDataProvider(): array
    {
        return [
            [
                []
            ],
            [
                [
                    'class' => WorkflowStep::class,
                    'choice_label' => 'label'
                ]
            ]
        ];
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->type,
                    EntityType::class => new EntityTypeStub($this->getDefinitions())
                ],
                []
            )
        ];
    }

    /**
     * @return Workflow[]
     */
    private function getWorkflows(): array
    {
        $definitions = $this->getDefinitions();
        $workflows = [];

        foreach ($definitions as $definition) {
            $workflow = $this->createMock(Workflow::class);
            $workflow->expects(self::any())
                ->method('getDefinition')
                ->willReturn($definition);

            $workflows[] = $workflow;
        }

        return $workflows;
    }

    /**
     * @return WorkflowDefinition[]
     */
    private function getDefinitions(): array
    {
        if (!$this->definitions) {
            $workflowDefinition = new WorkflowDefinition();
            $workflowDefinition->setName('wf_42');
            $workflowDefinition->setLabel('label42');
            $otherWorkflowDefinition = new WorkflowDefinition();
            $otherWorkflowDefinition->setName('wf_100');
            $otherWorkflowDefinition->setLabel('label100');
            $this->definitions = [
                'wf_42' => $workflowDefinition,
                'wf_100' => $otherWorkflowDefinition
            ];
        }

        return $this->definitions;
    }

    public function testFinishView(): void
    {
        $label = 'test_label';
        $translatedLabel = 'translated_test_label';

        $view = new FormView();
        $view->vars['choices'] = [new ChoiceView([], 'test', $label)];

        $this->translator->expects(self::once())
            ->method('trans')
            ->with($label, [], WorkflowTranslationHelper::TRANSLATION_DOMAIN)
            ->willReturn($translatedLabel);

        $this->type->finishView($view, $this->createMock(FormInterface::class), []);

        self::assertEquals([new ChoiceView([], 'test', $translatedLabel)], $view->vars['choices']);
    }
}
