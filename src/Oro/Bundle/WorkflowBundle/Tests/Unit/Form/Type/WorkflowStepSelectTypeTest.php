<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
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
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Translation\MessageCatalogueInterface;

class WorkflowStepSelectTypeTest extends FormIntegrationTestCase
{
    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowRegistry;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var MessageCatalogueInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translatorCatalogue;

    /** @var WorkflowStepSelectType */
    private $type;

    protected function setUp(): void
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->translatorCatalogue = $this->createMock(MessageCatalogueInterface::class);

        $translator = $this->createMock(Translator::class);
        $translator->expects($this->any())
            ->method('getCatalogue')
            ->willReturn($this->translatorCatalogue);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($label) {
                return 'translated_' . $label;
            });

        $this->type = new WorkflowStepSelectType($this->workflowRegistry, $translator);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $classMetadata->expects($this->any())
            ->method('getTypeOfField')
            ->willReturn('integer');
        $classMetadata->expects($this->any())
            ->method('getName')
            ->willReturn(WorkflowStep::class);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
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

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_workflow_step_select', $this->type->getBlockPrefix());
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

        $this->factory->create(WorkflowStepSelectType::class, null, $options);
    }

    public function testNormalizersByWorkflowName()
    {
        $options = ['workflow_name' => 'test'];
        $workflow = $this->getWorkflowDefinitionAwareClass(Workflow::class);

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with('test')
            ->willReturn($workflow);

        $this->assertQueryBuilderCalled();

        $this->factory->create(WorkflowStepSelectType::class, null, $options);
    }

    public function testNormalizersByEntityClass()
    {
        $options = ['workflow_entity_class' => \stdClass::class];
        $workflow = $this->getWorkflowDefinitionAwareClass(Workflow::class);

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with($options['workflow_entity_class'])
            ->willReturn(new ArrayCollection([$workflow]));

        $this->assertQueryBuilderCalled();

        $this->factory->create(WorkflowStepSelectType::class, null, $options);
    }

    public function testFinishViewWithOneWorkflow()
    {
        $step1 = $this->getWorkflowDefinitionAwareClass(WorkflowStep::class);
        $step3 = $this->getWorkflowDefinitionAwareClass(WorkflowStep::class);

        $view = new FormView();
        $view->vars['choices'] = [
            new ChoiceView($step1, 'step1', 'step1label'),
            new ChoiceView($step3, 'step2', 'step2label')
        ];

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with('test')
            ->willReturn(new \stdClass());

        $this->type->finishView($view, $this->createMock(FormInterface::class), ['workflow_name' => 'test']);

        $this->assertEquals('translated_step1label', $view->vars['choices'][0]->label);
        $this->assertEquals('translated_step2label', $view->vars['choices'][1]->label);
    }

    public function testFinishViewWithMoreThanOneWorkflow()
    {
        $step1 = $this->getWorkflowDefinitionAwareClass(WorkflowStep::class, 'wf_l1');
        $step2 = $this->getWorkflowDefinitionAwareClass(WorkflowStep::class, 'wf_l2');

        $view = new FormView();
        $view->vars['choices'] = [
            new ChoiceView($step1, 'step1', 'step1label'),
            new ChoiceView($step2, 'step2', 'step2label'),
        ];

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with(\stdClass::class)
            ->willReturn(new ArrayCollection([new \stdClass(), new \stdClass()]));

        $this->type->finishView(
            $view,
            $this->createMock(\Symfony\Component\Form\Test\FormInterface::class),
            ['workflow_entity_class' => \stdClass::class]
        );

        $this->assertEquals('translated_wf_l1: translated_step1label', $view->vars['choices'][0]->label);
        $this->assertEquals('translated_wf_l2: translated_step2label', $view->vars['choices'][1]->label);
    }

    public function testFinishViewException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Either "workflow_name" or "workflow_entity_class" must be set');

        $this->type->finishView(
            new FormView(),
            $this->createMock(\Symfony\Component\Form\Test\FormInterface::class),
            []
        );
    }

    private function getWorkflowDefinitionAwareClass(
        string $class,
        string $definitionLabel = null
    ): Workflow|WorkflowStep {
        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects($this->any())
            ->method('getLabel')
            ->willReturn($definitionLabel);

        $object = $this->createMock($class);
        $object->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        return $object;
    }

    private function assertQueryBuilderCalled()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getParameters')
            ->willReturn(new ArrayCollection());
        $qb->expects($this->once())
            ->method('where')
            ->with('ws.definition IN (:workflowDefinitions)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('workflowDefinitions', $this->isType('array'))
            ->willReturnSelf();
        $qb->expects($this->exactly(3))
            ->method('orderBy')
            ->willReturnSelf();

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getSQL')
            ->willReturn('SQL QUERY');
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository->expects($this->once())
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
                    'class' => 'OroWorkflowBundle:WorkflowStep',
                    'choice_label' => 'label'
                ]
            ]
        ];
    }
}
