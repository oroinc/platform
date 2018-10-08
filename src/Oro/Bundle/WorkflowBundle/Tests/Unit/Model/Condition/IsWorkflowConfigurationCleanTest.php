<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Condition;

use Oro\Bundle\WorkflowBundle\Configuration\Checker\ConfigurationChecker;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Condition\IsWorkflowConfigurationClean;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

class IsWorkflowConfigurationCleanTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigurationChecker|\PHPUnit\Framework\MockObject\MockObject */
    protected $checker;

    /** @var IsWorkflowConfigurationClean */
    protected $condition;

    protected function setUp()
    {
        $this->checker = $this->createMock(ConfigurationChecker::class);

        $this->condition = new IsWorkflowConfigurationClean($this->checker);
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    public function testGetName()
    {
        $this->assertEquals(IsWorkflowConfigurationClean::NAME, $this->condition->getName());
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 1 element, but 0 given.
     */
    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->condition->initialize([]);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Option "workflow" is required.
     */
    public function testInitializeFailsWhenOptionNotExpressionInterface()
    {
        $this->condition->initialize([1 => 'anything']);
    }

    public function testEvaluateNotSupported()
    {
        $this->checker->expects($this->never())->method($this->anything());

        $this->assertSame($this->condition, $this->condition->initialize([new PropertyPath('data')]));
        $this->assertTrue($this->condition->evaluate(['data' => new \stdClass()]));
    }

    /**
     * @dataProvider evaluateDataProvider
     *
     * @param bool $expected
     */
    public function testEvaluate($expected)
    {
        $configuration = [
            WorkflowConfiguration::NODE_TRANSITIONS => [],
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => []
        ];

        $workflow = new WorkflowDefinition();
        $workflow->setConfiguration($configuration);

        $this->checker->expects($this->once())->method('isClean')->with($configuration)->willReturn($expected);

        $this->assertSame($this->condition, $this->condition->initialize([new PropertyPath('data')]));
        $this->assertEquals($expected, $this->condition->evaluate(['data' => $workflow]));
    }

    /**
     * @return \Generator
     */
    public function evaluateDataProvider()
    {
        yield 'clean configuration' => [
            'expected' => true
        ];

        yield 'not clean configuration' => [
            'expected' => false
        ];
    }
}
