<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Condition;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\Condition\CurrentStepNameIsEqual;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\Testing\Unit\EntityTrait;

class CurrentStepNameIsEqualTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const STEP_NAME = 'TestStep';
    const WORKFLOW_NAME = 'TestWorkflow';

    /** @var CurrentStepNameIsEqual */
    protected $condition;

    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $this->condition = new CurrentStepNameIsEqual($this->workflowManager);
    }

    public function testGetName()
    {
        $this->assertEquals(CurrentStepNameIsEqual::NAME, $this->condition->getName());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Missing "step_name" option
     */
    public function testInitializeInvalid()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([])
        );
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([
                'main_entity' => new \stdClass(),
                'step_name' => self::STEP_NAME,
                'workflow' => self::WORKFLOW_NAME
            ])
        );
    }

    /**
     * @dataProvider evaluateProvider
     *
     * @param string $stepName
     * @param bool $expected
     */
    public function testEvaluate($stepName, $expected)
    {
        $context = $this->createMock(TransitionContext::class);
        $user = $this->getEntity(User::class);
        $workflowItem = $this->getEntity(WorkflowItem::class, [
            'current_step' => $this->getEntity(WorkflowStep::class, [
                'name' => $stepName
            ])
        ]);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItem')
            ->with($user, self::WORKFLOW_NAME)
            ->willReturn($workflowItem);

        $this->condition->initialize(
            [
                'main_entity' => $user,
                'step_name' => self::STEP_NAME,
                'workflow' => self::WORKFLOW_NAME
            ]
        );
        $this->assertEquals($expected, $this->condition->evaluate($context));
    }

    /**
     * @return array
     */
    public function evaluateProvider()
    {
        return [
            'step_name_is_not_equal' => [
                'stepName' => 'Wrong Step Name',
                'expected' => false
            ],
            'step_name_is_equal' => [
                'stepName' => self::STEP_NAME,
                'expected' => true
            ]
        ];
    }

    public function testToArray()
    {
        $stdClass = new \stdClass();
        $this->condition->initialize([
            'main_entity' => $stdClass,
            'step_name' => self::STEP_NAME,
            'workflow' => self::WORKFLOW_NAME
        ]);
        $result = $this->condition->toArray();

        $key = '@'.CurrentStepNameIsEqual::NAME;

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        $this->assertInternalType('array', $resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains($stdClass, $resultSection['parameters']);
    }

    public function testCompile()
    {
        $toStringStub = new User();
        $options = [
            'main_entity' => $toStringStub,
            'step_name' => self::STEP_NAME,
            'workflow' => self::WORKFLOW_NAME
        ];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        $this->assertEquals(
            sprintf(
                '$factory->create(\'%s\', [\'%s\', %s, \'%s\'])',
                CurrentStepNameIsEqual::NAME,
                self::STEP_NAME,
                $toStringStub,
                self::WORKFLOW_NAME
            ),
            $result
        );
    }
}
