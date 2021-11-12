<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TransitionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var TransitionOptionsResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $optionsResolver;

    /** @var Transition */
    private $transition;

    protected function setUp(): void
    {
        $this->optionsResolver = $this->createMock(TransitionOptionsResolver::class);

        $this->transition = new Transition($this->optionsResolver);
    }

    public function testAccessors(): void
    {
        self::assertPropertyAccessors(
            $this->transition,
            [
                ['name', 'test'],
                ['buttonLabel', 'test_button_label'],
                ['buttonTitle', 'test_button_title'],
                ['hidden', true, false],
                ['start', true, false],
                ['unavailableHidden', true],
                ['stepTo', $this->getStep('testStep')],
                ['frontendOptions', ['key' => 'value'], []],
                ['formType', 'custom_workflow_transition'],
                ['displayType', 'page'],
                ['destinationPage', 'destination'],
                ['formOptions', ['one', 'two'], []],
                ['pageTemplate', '@OroWorkflow/Test/page_template.html.twig'],
                ['dialogTemplate', '@OroWorkflow/Test/dialog_template.html.twig'],
                ['scheduleCron', '1 * * * *'],
                ['scheduleFilter', "e.field < DATE_ADD(NOW(), 1, 'day')"],
                ['scheduleCheckConditions', true],
                ['preAction', $this->createMock(ActionInterface::class)],
                ['preCondition', $this->createMock(ExpressionInterface::class)],
                ['condition', $this->createMock(ExpressionInterface::class)],
                ['action', $this->createMock(ActionInterface::class)],
                ['initEntities', ['TEST_ENTITY_1', 'TEST_ENTITY_2', 'TEST_ENTITY_3']],
                ['initRoutes', ['TEST_ROUTE_1', 'TEST_ROUTE_2', 'TEST_ROUTE_3']],
                ['initContextAttribute', 'testInitContextAttribute'],
                ['message', 'test message'],
            ]
        );
    }

    public function testToString(): void
    {
        $this->transition->setName('test_transition');

        self::assertEquals('test_transition', (string)$this->transition);
    }

    /**
     * @dataProvider isAllowedDataProvider
     */
    public function testIsAllowed(?bool $isAllowed, bool $expected): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $errors = new ArrayCollection();
        $expectedError = ['message' => 'test message', 'parameters' => ['param' => 'value']];

        if (null !== $isAllowed) {
            $condition = $this->createMock(ExpressionInterface::class);
            $condition->expects(self::once())
                ->method('evaluate')
                ->with($workflowItem)
                ->willReturnCallback(
                    static function (WorkflowItem $workflowItem, Collection $errors) use ($isAllowed, $expectedError) {
                        if ($isAllowed === false) {
                            $errors->add($expectedError);
                        }

                        return $isAllowed;
                    }
                );
            $this->transition->setCondition($condition);
        }

        self::assertEquals($expected, $this->transition->isAllowed($workflowItem, $errors));
        self::assertEquals($isAllowed === false ? [$expectedError] : [], $errors->toArray());
    }

    public function isAllowedDataProvider(): array
    {
        return [
            'allowed' => [
                'isAllowed' => true,
                'expected' => true
            ],
            'not allowed' => [
                'isAllowed' => false,
                'expected' => false,
            ],
            'no condition' => [
                'isAllowed' => null,
                'expected' => true,
            ],
        ];
    }

    public function testIsPreConditionAllowedWithPreActions(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $errors = new ArrayCollection();

        $action = $this->createMock(ActionInterface::class);
        $action->expects(self::once())
            ->method('execute')
            ->with($workflowItem);
        $this->transition->setPreAction($action);

        $condition = $this->createMock(ExpressionInterface::class);
        $condition->expects(self::once())
            ->method('evaluate')
            ->with($workflowItem)
            ->willReturn(true);
        $this->transition->setCondition($condition);

        self::assertTrue($this->transition->isAllowed($workflowItem, $errors));
        self::assertEmpty($errors->toArray());
    }

    /**
     * @dataProvider isAllowedDataProvider
     */
    public function testIsAvailableWithForm(?bool $isAllowed, bool $expected): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $errors = new ArrayCollection();
        $expectedError = ['message' => 'test message', 'parameters' => ['param' => 'value']];

        $this->transition->setFormOptions(['key' => 'value']);

        if (null !== $isAllowed) {
            $condition = $this->createMock(ExpressionInterface::class);
            $condition->expects(self::once())
                ->method('evaluate')
                ->with($workflowItem)
                ->willReturnCallback(
                    static function (WorkflowItem $workflowItem, Collection $errors) use ($isAllowed, $expectedError) {
                        if ($isAllowed === false) {
                            $errors->add($expectedError);
                        }

                        return $isAllowed;
                    }
                );
            $this->transition->setPreCondition($condition);
        }
        $this->optionsResolver->expects(self::once())
            ->method('resolveTransitionOptions')
            ->with($this->transition, $workflowItem);

        self::assertEquals($expected, $this->transition->isAvailable($workflowItem, $errors));
        self::assertEquals($isAllowed === false ? [$expectedError] : [], $errors->toArray());
    }

    /**
     * @dataProvider isAvailableDataProvider
     */
    public function testIsAvailableWithoutForm(?bool $isAllowed, ?bool $isAvailable, bool $expected): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $errors = new ArrayCollection();
        $error1 = ['message' => 'test message 1', 'parameters' => ['param1' => 'value1']];
        $error2 = ['message' => 'test message 2', 'parameters' => ['param2' => 'value2']];

        if (null !== $isAvailable) {
            $preCondition = $this->createMock(ExpressionInterface::class);
            $preCondition->expects(self::any())
                ->method('evaluate')
                ->with($workflowItem)
                ->willReturnCallback(
                    static function (WorkflowItem $workflowItem, Collection $errors) use ($isAvailable, $error1) {
                        if ($isAvailable === false) {
                            $errors->add($error1);
                        }

                        return $isAvailable;
                    }
                );
            $this->transition->setPreCondition($preCondition);
        }
        if (null !== $isAllowed) {
            $condition = $this->createMock(ExpressionInterface::class);
            $condition->expects(self::any())
                ->method('evaluate')
                ->with($workflowItem)
                ->willReturnCallback(
                    static function (WorkflowItem $workflowItem, Collection $errors) use ($isAllowed, $error2) {
                        if ($isAllowed === false) {
                            $errors->add($error2);
                        }

                        return $isAllowed;
                    }
                );
            $this->transition->setCondition($condition);
        }

        self::assertEquals($expected, $this->transition->isAvailable($workflowItem, $errors));
        self::assertEquals(
            array_merge(
                $isAvailable === false ? [$error1] : [],
                $isAvailable === true && $isAllowed === false ? [$error2] : []
            ),
            $errors->toArray()
        );
    }

    public function isAvailableDataProvider(): array
    {
        return [
            'allowed' => [
                'isAllowed' => true,
                'isAvailable' => true,
                'expected' => true
            ],
            'not allowed #1' => [
                'isAllowed' => false,
                'isAvailable' => true,
                'expected' => false,
            ],
            'not allowed #2' => [
                'isAllowed' => true,
                'isAvailable' => false,
                'expected' => false,
            ],
            'not allowed #3' => [
                'isAllowed' => false,
                'isAvailable' => false,
                'expected' => false,
            ],
            'no conditions' => [
                'isAllowed' => null,
                'isAvailable' => null,
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider transitDisallowedDataProvider
     */
    public function testTransitNotAllowed(bool $preConditionAllowed, bool $conditionAllowed): void
    {
        $this->expectException(ForbiddenTransitionException::class);
        $this->expectExceptionMessage('Transition "test" is not allowed.');

        $workflowItem = $this->createMock(WorkflowItem::class);
        $errors = new ArrayCollection();

        $workflowItem->expects(self::never())
            ->method('setCurrentStep');

        $preCondition = $this->createMock(ExpressionInterface::class);
        $preCondition->expects(self::any())
            ->method('evaluate')
            ->with($workflowItem, $errors)
            ->willReturn($preConditionAllowed);

        $condition = $this->createMock(ExpressionInterface::class);
        $condition->expects(self::any())
            ->method('evaluate')
            ->with($workflowItem, $errors)
            ->willReturn($conditionAllowed);

        $action = $this->createMock(ActionInterface::class);
        $action->expects(self::never())
            ->method('execute');

        $this->transition->setName('test')
            ->setPreCondition($preCondition)
            ->setCondition($condition)
            ->setAction($action)
            ->transit($workflowItem, $errors);
    }

    public function transitDisallowedDataProvider(): array
    {
        return [
            [false, false],
            [false, true],
            [true, false]
        ];
    }

    /**
     * @dataProvider transitDataProvider
     */
    public function testTransit(bool $isFinal, bool $hasAllowedTransition): void
    {
        $currentStepEntity = $this->createMock(WorkflowStep::class);

        $step = $this->getStep('currentStep', $isFinal, $hasAllowedTransition);

        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $workflowDefinition->expects(self::once())
            ->method('getStepByName')
            ->with($step->getName())
            ->willReturn($currentStepEntity);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);
        $workflowItem->expects(self::once())
            ->method('setCurrentStep')
            ->with($currentStepEntity);

        $preCondition = $this->createMock(ExpressionInterface::class);
        $preCondition->expects(self::once())
            ->method('evaluate')
            ->with($workflowItem)
            ->willReturn(true);

        $condition = $this->createMock(ExpressionInterface::class);
        $condition->expects(self::once())
            ->method('evaluate')
            ->with($workflowItem)
            ->willReturn(true);

        $action = $this->createMock(ActionInterface::class);
        $action->expects(self::once())
            ->method('execute')
            ->with($workflowItem);

        $this->transition
            ->setPreCondition($preCondition)
            ->setCondition($condition)
            ->setAction($action)
            ->setStepTo($step)
            ->transit($workflowItem);
    }

    public function transitDataProvider(): array
    {
        return [
            [true, true],
            [true, false],
            [false, false]
        ];
    }

    private function getStep(string $name, bool $isFinal = false, bool $hasAllowedTransitions = true): Step
    {
        $step = $this->createMock(Step::class);
        $step->expects(self::any())
            ->method('getName')
            ->willReturn($name);
        $step->expects(self::any())
            ->method('isFinal')
            ->willReturn($isFinal);
        $step->expects(self::any())
            ->method('hasAllowedTransitions')
            ->willReturn($hasAllowedTransitions);

        return $step;
    }

    public function testHasForm(): void
    {
        self::assertFalse($this->transition->hasForm()); // by default transition has form

        $this->transition->setFormOptions(['key' => 'value']);
        self::assertFalse($this->transition->hasForm());

        $this->transition->setFormOptions(['attribute_fields' => []]);
        self::assertFalse($this->transition->hasForm());

        $this->transition->setFormOptions(['attribute_fields' => ['key' => 'value']]);
        self::assertTrue($this->transition->hasForm());
    }

    public function testHasFormWithFormConfiguration(): void
    {
        self::assertFalse($this->transition->hasForm()); // by default transition has form

        $this->transition->setFormOptions(['key' => 'value']);
        self::assertFalse($this->transition->hasForm());

        $this->transition->setFormOptions(['configuration' => []]);
        self::assertFalse($this->transition->hasForm());

        $this->transition->setFormOptions(['configuration' => ['key' => 'value']]);
        self::assertTrue($this->transition->hasForm());
    }

    public function testHasFormForPage(): void
    {
        self::assertFalse($this->transition->hasForm()); // by default transition has form

        $this->transition->setDisplayType('page');
        self::assertTrue($this->transition->hasForm());
    }

    /**
     * @dataProvider initContextProvider
     */
    public function testIsNotEmptyInitContext(array $entities, array $routes, array $datagrids, bool $result): void
    {
        $this->transition->setInitEntities($entities)
            ->setInitRoutes($routes)
            ->setInitDatagrids($datagrids);
        self::assertSame($result, $this->transition->isEmptyInitOptions());
    }

    public function initContextProvider(): array
    {
        return [
            'empty' => [
                'entities' => [],
                'routes' => [],
                'datagrids' => [],
                'result' => true
            ],
            'only entity' => [
                'entities' => ['entity'],
                'routes' => [],
                'datagrids' => [],
                'result' => false
            ],
            'only route' => [
                'entities' => [],
                'routes' => ['route'],
                'datagrids' => [],
                'result' => false
            ],
            'only datagrid' => [
                'entities' => [],
                'routes' => [],
                'datagrids' => ['datagrid'],
                'result' => false
            ],
            'full' => [
                'entities' => ['entity'],
                'routes' => ['route'],
                'datagrids' => ['datagrid'],
                'result' => false
            ],
            'full with arrays' => [
                'entities' => ['entity1', 'entity2'],
                'routes' => ['route1', 'route2'],
                'datagrids' => ['datagrid1', 'datagrid2'],
                'result' => false
            ]
        ];
    }

    public function testFormOptionsConfiguration(): void
    {
        self::assertEquals([], $this->transition->getFormOptions());
        self::assertFalse($this->transition->hasFormConfiguration());

        $formConfiguration = [
            'handler' => 'handler',
            'template' => 'template',
            'data_provider' => 'data_provider',
            'data_attribute' => 'data_attribute',
        ];
        $formOptions = [WorkflowConfiguration::NODE_FORM_OPTIONS_CONFIGURATION => $formConfiguration];

        $this->transition->setFormOptions($formOptions);

        self::assertTrue($this->transition->hasFormConfiguration());
        self::assertEquals($formConfiguration['handler'], $this->transition->getFormHandler());
        self::assertEquals($formConfiguration['template'], $this->transition->getFormTemplate());
        self::assertEquals($formConfiguration['data_provider'], $this->transition->getFormDataProvider());
        self::assertEquals($formConfiguration['data_attribute'], $this->transition->getFormDataAttribute());
    }
}
