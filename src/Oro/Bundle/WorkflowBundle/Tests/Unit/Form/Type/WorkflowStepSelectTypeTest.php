<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowStepSelectType;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
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
    /** @var \PHPUnit\Framework\MockObject\MockObject|WorkflowRegistry */
    private $workflowRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityRepository */
    private $repository;

    /** @var WorkflowStepSelectType */
    private $type;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Translator */
    private $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MessageCatalogueInterface */
    private $translatorCatalogue;

    protected function setUp(): void
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);

        $this->translatorCatalogue = $this->createMock(MessageCatalogueInterface::class);

        $this->translator = $this->createMock(Translator::class);
        $this->translator->expects($this->any())
            ->method('getCatalogue')
            ->willReturn($this->translatorCatalogue);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($label) {
                return 'transtaled_' . $label;
            });

        $this->repository = $this->createMock(EntityRepository::class);

        $this->type = new WorkflowStepSelectType($this->workflowRegistry, $this->translator);

        parent::setUp();
    }

    protected function getExtensions()
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

        $mockEntityManager = $this->createMock(EntityManager::class);
        $mockEntityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);
        $mockEntityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $mockRegistry = $this->createMock(Registry::class);
        $mockRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($mockEntityManager);

        $mockEntityType = new EntityType($mockRegistry);

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    EntityType::class => $mockEntityType
                ],
                []
            )
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(WorkflowStepSelectType::NAME, $this->type->getName());
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
        $step2 = $this->getWorkflowDefinitionAwareClass(WorkflowStep::class);
        $step3 = $this->getWorkflowDefinitionAwareClass(WorkflowStep::class);

        $view = new FormView();
        $view->vars['choices'] = [
            new ChoiceView($step1, 'step1', 'step1label'),
            new ChoiceView($step2, 'step2', 'step2label'),
            new ChoiceView($step3, 'step3', 'step3label')
        ];

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with('test')
            ->willReturn(new \stdClass());

        $this->translatorCatalogue->expects($this->exactly(3))
            ->method('has')
            ->willReturnMap([
                ['step1label', WorkflowTranslationHelper::TRANSLATION_DOMAIN, true],
                ['step2label', WorkflowTranslationHelper::TRANSLATION_DOMAIN, false],
                ['step3label', WorkflowTranslationHelper::TRANSLATION_DOMAIN, true]
            ]);

        $this->type->finishView($view, $this->createMock(FormInterface::class), ['workflow_name' => 'test']);

        $this->assertEquals('transtaled_step1label', $view->vars['choices'][0]->label);
        $this->assertEquals('step2label', $view->vars['choices'][1]->label);
        $this->assertEquals('transtaled_step3label', $view->vars['choices'][2]->label);
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

        $this->translatorCatalogue->expects($this->any())
            ->method('has')
            ->willReturn(true);

        $this->type->finishView(
            $view,
            $this->createMock(\Symfony\Component\Form\Test\FormInterface::class),
            ['workflow_entity_class' => \stdClass::class]
        );

        $this->assertEquals('transtaled_wf_l1: transtaled_step1label', $view->vars['choices'][0]->label);
        $this->assertEquals('transtaled_wf_l2: transtaled_step2label', $view->vars['choices'][1]->label);
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

    /**
     * @return Workflow|WorkflowStep
     */
    private function getWorkflowDefinitionAwareClass(string $class, string $definitionLabel = null)
    {
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
        $func = new Expr\Func('ws.definition IN', ':workflowDefinitions');

        $expr = $this->createMock(Expr::class);
        $expr->expects($this->once())
            ->method('in')
            ->with('ws.definition', ':workflowDefinitions')
            ->willReturn($func);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->any())
            ->method('orderBy')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with($func)
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('workflowDefinitions', $this->isType('array'))
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('getParameters')
            ->willReturn(new ArrayCollection());
        $qb->expects($this->any())
            ->method('expr')
            ->willReturn($expr);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->any())
            ->method('execute')
            ->willReturn([]);
        $query->expects($this->any())
            ->method('getSQL')
            ->willReturn('SQL QUERY');
        $qb->expects($this->any())
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
