<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowStepSelectType;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class WorkflowStepSelectTypeTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|WorkflowRegistry */
    protected $workflowRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository */
    protected $repository;

    /** @var WorkflowStepSelectType */
    protected $type;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
    protected $translator;

    protected function setUp()
    {
        $this->workflowRegistry = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())->method('trans')->willReturnArgument(0);

        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new WorkflowStepSelectType($this->workflowRegistry, $this->translator);

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $classMetadata = $this->getMockBuilder('\Doctrine\Common\Persistence\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->expects($this->any())->method('getIdentifierFieldNames')->willReturn(['id']);
        $classMetadata->expects($this->any())->method('getTypeOfField')->willReturn('integer');
        $classMetadata->expects($this->any())
            ->method('getName')
            ->willReturn('Oro\Bundle\WorkflowBundle\Entity\WorkflowStep');

        $mockEntityManager = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityManager->expects($this->any())->method('getRepository')->willReturn($this->repository);
        $mockEntityManager->expects($this->any())->method('getClassMetadata')->willReturn($classMetadata);

        $mockRegistry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManagerForClass'])
            ->getMock();
        $mockRegistry->expects($this->any())->method('getManagerForClass')->willReturn($mockEntityManager);

        $mockEntityType = $this->getMockBuilder('Symfony\Bridge\Doctrine\Form\Type\EntityType')
            ->setMethods(['getName'])
            ->setConstructorArgs([$mockRegistry])
            ->getMock();
        $mockEntityType->expects($this->any())->method('getName')->willReturn('entity');

        return [new PreloadedExtension([$mockEntityType->getName() => $mockEntityType], [])];
    }

    public function testGetName()
    {
        $this->assertEquals(WorkflowStepSelectType::NAME, $this->type->getName());
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

    public function testNormalizersByWorkflowName()
    {
        $options = ['workflow_name' => 'test'];
        $workflow = $this->getWorkflowDefinitionAwareClassMock('Oro\Bundle\WorkflowBundle\Model\Workflow');

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with('test')
            ->willReturn($workflow);

        $this->assertQueryBuilderCalled();

        $this->factory->create($this->type, null, $options);
    }

    public function testNormalizersByEntityClass()
    {
        $options = ['workflow_entity_class' => '\stdClass'];
        $workflow = $this->getWorkflowDefinitionAwareClassMock('Oro\Bundle\WorkflowBundle\Model\Workflow');

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with($options['workflow_entity_class'])
            ->willReturn(new ArrayCollection([$workflow]));

        $this->assertQueryBuilderCalled();

        $this->factory->create($this->type, null, $options);
    }

    public function testFinishViewWithOneWorkflow()
    {
        $step1 = $this->getWorkflowDefinitionAwareClassMock('Oro\Bundle\WorkflowBundle\Entity\WorkflowStep');
        $step2 = $this->getWorkflowDefinitionAwareClassMock('Oro\Bundle\WorkflowBundle\Entity\WorkflowStep');

        $view = new FormView();
        $view->vars['choices'] = [
            new ChoiceView($step1, 'step1', 'step1label'),
            new ChoiceView($step2, 'step2', 'step2label'),
        ];

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with('test')
            ->willReturn(new \stdClass());

        $this->type->finishView(
            $view,
            $this->getMock('Symfony\Component\Form\Test\FormInterface'),
            ['workflow_name' => 'test']
        );

        $this->assertEquals('step1label', $view->vars['choices'][0]->label);
        $this->assertEquals('step2label', $view->vars['choices'][1]->label);
    }

    public function testFinishViewWithMoreThanOneWorkflow()
    {
        $step1 = $this->getWorkflowDefinitionAwareClassMock('Oro\Bundle\WorkflowBundle\Entity\WorkflowStep', 'wf_l1');
        $step2 = $this->getWorkflowDefinitionAwareClassMock('Oro\Bundle\WorkflowBundle\Entity\WorkflowStep', 'wf_l2');

        $view = new FormView();
        $view->vars['choices'] = [
            new ChoiceView($step1, 'step1', 'step1label'),
            new ChoiceView($step2, 'step2', 'step2label'),
        ];

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with('\stdClass')
            ->willReturn(new ArrayCollection([new \stdClass(), new \stdClass()]));

        $this->type->finishView(
            $view,
            $this->getMock('Symfony\Component\Form\Test\FormInterface'),
            ['workflow_entity_class' => '\stdClass']
        );

        $this->assertEquals('wf_l1: step1label', $view->vars['choices'][0]->label);
        $this->assertEquals('wf_l2: step2label', $view->vars['choices'][1]->label);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Either "workflow_name" or "workflow_entity_class" must be set
     */
    public function testFinishViewException()
    {
        $this->type->finishView(
            new FormView(),
            $this->getMock('Symfony\Component\Form\Test\FormInterface'),
            []
        );
    }

    /**
     * @param string $class
     * @param string $definitionLabel
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Workflow|WorkflowStep
     */
    protected function getWorkflowDefinitionAwareClassMock($class, $definitionLabel = null)
    {
        $definition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->any())->method('getLabel')->willReturn($definitionLabel);

        $object = $this->getMockBuilder($class)->disableOriginalConstructor()->getMock();
        $object->expects($this->any())->method('getDefinition')->willReturn($definition);

        return $object;
    }

    protected function assertQueryBuilderCalled()
    {
        $func = new Expr\Func('ws.definition IN', ':workflowDefinitions');

        /** @var Expr|\PHPUnit_Framework_MockObject_MockObject $expr */
        $expr = $this->getMockBuilder('Doctrine\ORM\Query\Expr')->disableOriginalConstructor()->getMock();
        $expr->expects($this->once())
            ->method('in')
            ->with('ws.definition', ':workflowDefinitions')
            ->willReturn($func);

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->any())->method('orderBy')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with($func)->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('workflowDefinitions', $this->isType('array'))
            ->willReturnSelf();
        $qb->expects($this->any())->method('getParameters')->willReturn(new ArrayCollection());
        $qb->expects($this->any())->method('expr')->willReturn($expr);

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMockForAbstractClass();
        $query->expects($this->any())->method('execute')->willReturn([]);
        $query->expects($this->any())->method('getSQL')->willReturn('SQL QUERY');
        $qb->expects($this->any())->method('getQuery')->willReturn($query);

        $this->repository->expects($this->once())->method('createQueryBuilder')->with('ws')->willReturn($qb);
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

    public function testFinishView()
    {
        $view = new FormView();
        $child = new ChoiceView([], 'test', 'test');
        $view->vars['choices'] = [$child];
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->translator->expects($this->atLeastOnce())
            ->method('trans')
            ->with('test', [], WorkflowTranslationHelper::TRANSLATION_DOMAIN)
            ->willReturnArgument(0);
        $this->type->finishView($view, $form, ['workflow_name' => 'test_wf']);
    }
}
