<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowStepSelectType;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Translation\MessageCatalogueInterface;

class WorkflowStepSelectTypeTest extends FormIntegrationTestCase
{
    private WorkflowRegistry&MockObject $workflowRegistry;
    private EntityRepository&MockObject $repository;
    private MessageCatalogueInterface&MockObject $translatorCatalogue;
    private WorkflowStepSelectType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->translatorCatalogue = $this->createMock(MessageCatalogueInterface::class);

        $translator = $this->createMock(Translator::class);
        $translator->expects(self::any())
            ->method('getCatalogue')
            ->willReturn($this->translatorCatalogue);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($label) {
                return 'translated_' . $label;
            });

        $this->type = new WorkflowStepSelectType($this->workflowRegistry, $translator);

        parent::setUp();
    }

    #[\Override]
    protected function getExtensions(): array
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $classMetadata->expects(self::any())
            ->method('getTypeOfField')
            ->willReturn('integer');
        $classMetadata->expects(self::any())
            ->method('getName')
            ->willReturn(WorkflowStep::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::any())
            ->method('getRepository')
            ->willReturn($this->repository);
        $em->expects(self::any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($em);

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    new EntityType($doctrine)
                ],
                []
            )
        ];
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_workflow_step_select', $this->type->getBlockPrefix());
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

        $this->factory->create(WorkflowStepSelectType::class, null, $options);
    }

    public function testNormalizersByWorkflowName(): void
    {
        $options = ['workflow_name' => 'test'];
        $workflow = $this->getWorkflowDefinitionAwareClass(Workflow::class);

        $this->workflowRegistry->expects(self::once())
            ->method('getWorkflow')
            ->with('test')
            ->willReturn($workflow);

        $this->assertQueryBuilderCalled();

        $this->factory->create(WorkflowStepSelectType::class, null, $options);
    }

    public function testNormalizersByEntityClass(): void
    {
        $options = ['workflow_entity_class' => Workflow::class];
        $workflow = $this->getWorkflowDefinitionAwareClass(Workflow::class);

        $this->workflowRegistry->expects(self::once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with($options['workflow_entity_class'])
            ->willReturn(new ArrayCollection([$workflow]));

        $this->assertQueryBuilderCalled();

        $this->factory->create(WorkflowStepSelectType::class, null, $options);
    }

    public function testFinishViewWithOneWorkflow(): void
    {
        $step1 = $this->getWorkflowDefinitionAwareClass(WorkflowStep::class);
        $step3 = $this->getWorkflowDefinitionAwareClass(WorkflowStep::class);

        $view = new FormView();
        $view->vars['choices'] = [
            new ChoiceView($step1, 'step1', 'step1label'),
            new ChoiceView($step3, 'step2', 'step2label')
        ];

        $this->workflowRegistry->expects(self::once())
            ->method('getWorkflow')
            ->with('test')
            ->willReturn($this->createMock(Workflow::class));

        $this->type->finishView($view, $this->createMock(FormInterface::class), ['workflow_name' => 'test']);

        self::assertEquals('translated_step1label', $view->vars['choices'][0]->label);
        self::assertEquals('translated_step2label', $view->vars['choices'][1]->label);
    }

    public function testFinishViewWithMoreThanOneWorkflow(): void
    {
        $step1 = $this->getWorkflowDefinitionAwareClass(WorkflowStep::class, 'wf_l1');
        $step2 = $this->getWorkflowDefinitionAwareClass(WorkflowStep::class, 'wf_l2');

        $view = new FormView();
        $view->vars['choices'] = [
            new ChoiceView($step1, 'step1', 'step1label'),
            new ChoiceView($step2, 'step2', 'step2label'),
        ];

        $this->workflowRegistry->expects(self::once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with(Workflow::class)
            ->willReturn(new ArrayCollection([
                $this->createMock(Workflow::class),
                $this->createMock(Workflow::class)
            ]));

        $this->type->finishView(
            $view,
            $this->createMock(FormInterface::class),
            ['workflow_entity_class' => Workflow::class]
        );

        self::assertEquals('translated_wf_l1: translated_step1label', $view->vars['choices'][0]->label);
        self::assertEquals('translated_wf_l2: translated_step2label', $view->vars['choices'][1]->label);
    }

    public function testFinishViewException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Either "workflow_name" or "workflow_entity_class" must be set');

        $this->type->finishView(
            new FormView(),
            $this->createMock(FormInterface::class),
            []
        );
    }

    private function getWorkflowDefinitionAwareClass(
        string $class,
        ?string $definitionLabel = null
    ): Workflow|WorkflowStep {
        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects(self::any())
            ->method('getLabel')
            ->willReturn($definitionLabel);

        $object = $this->createMock($class);
        $object->expects(self::any())
            ->method('getDefinition')
            ->willReturn($definition);

        return $object;
    }

    private function assertQueryBuilderCalled(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::once())
            ->method('getParameters')
            ->willReturn(new ArrayCollection());
        $qb->expects(self::once())
            ->method('where')
            ->with('ws.definition IN (:workflowDefinitions)')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('setParameter')
            ->with('workflowDefinitions', $this->isType('array'))
            ->willReturnSelf();
        $qb->expects(self::exactly(3))
            ->method('orderBy')
            ->willReturnSelf();

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getSQL')
            ->willReturn('SQL QUERY');
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('ws')
            ->willReturn($qb);
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
}
