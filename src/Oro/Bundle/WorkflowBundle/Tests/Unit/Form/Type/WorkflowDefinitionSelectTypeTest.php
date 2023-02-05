<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionSelectType;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class WorkflowDefinitionSelectTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    private const WORKFLOW_ENTITY_NAME = 'stdClass';

    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowRegistry;

    /** @var WorkflowDefinition[] */
    private $definitions = [];

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var WorkflowDefinitionSelectType */
    private $type;

    protected function setUp(): void
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->type = new WorkflowDefinitionSelectType($this->workflowRegistry, $this->translator);
        parent::setUp();
    }

    public function testSubmitWithWorkflowNameOption()
    {
        $workflows = $this->getWorkflows();

        /** @var Workflow $workflow */
        $workflow = array_shift($workflows);
        $definition = $workflow->getDefinition();

        $this->workflowRegistry->expects($this->once())
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

    public function testSubmitWithEntityClassOption()
    {
        $definitions = $this->getDefinitions();

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with(self::WORKFLOW_ENTITY_NAME)
            ->willReturn($this->getWorkflows());

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
            $this->assertTrue($formConfig->hasOption($key));
            $this->assertEquals($value, $formConfig->getOption($key));
        }

        $this->assertNull($form->getData());

        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function testGetParent()
    {
        $this->assertEquals(EntityType::class, $this->type->getParent());
    }

    /**
     * @dataProvider incorrectOptionsDataProvider
     */
    public function testNormalizersException(array $options)
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
                    'class' => 'OroWorkflowBundle:WorkflowStep',
                    'choice_label' => 'label'
                ]
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
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
            $workflow->expects($this->any())
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
            $this->definitions = [
                'wf_42' => $this->getEntity(
                    WorkflowDefinition::class,
                    ['name' => 'wf_42', 'label' => 'label42']
                ),
                'wf_100' => $this->getEntity(
                    WorkflowDefinition::class,
                    ['name' => 'wf_100', 'label' => 'label100']
                )
            ];
        }

        return $this->definitions;
    }

    public function testFinishView()
    {
        $label = 'test_label';
        $translatedLabel = 'translated_test_label';

        $view = new FormView();
        $view->vars['choices'] = [new ChoiceView([], 'test', $label)];

        $this->translator->expects($this->once())
            ->method('trans')
            ->with($label, [], WorkflowTranslationHelper::TRANSLATION_DOMAIN)
            ->willReturn($translatedLabel);

        $this->type->finishView($view, $this->createMock(FormInterface::class), []);

        $this->assertEquals([new ChoiceView([], 'test', $translatedLabel)], $view->vars['choices']);
    }
}
