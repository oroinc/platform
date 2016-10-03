<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger\Verifier;

use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\TransitionTriggerVerifierException;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier\TransitionEventTriggerExpressionVerifier;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityStub;

class TransitionEventTriggerExpressionVerifierTest extends \PHPUnit_Framework_TestCase
{
    /** @var TransitionEventTriggerExpressionVerifier */
    private $verifier;

    protected function setUp()
    {
        $this->verifier = new TransitionEventTriggerExpressionVerifier();
    }

    /**
     * Covers return statement when trigger without expression comes
     */
    public function testNotVerifyIfNoRequireExpression()
    {
        $trigger = new TransitionEventTrigger();

        $this->verifier->verifyTrigger($trigger);
    }

    /**
     * Covers normal configuration processing
     */
    public function testVerificationOk()
    {
        $trigger = $this->buildEventTriggerWithExpression(
            'wd.getName() !== wi.getId() and entity.getId() === mainEntity.getId()',
            EntityStub::class,
            EntityStub::class
        );

        $this->verifier->verifyTrigger($trigger);
    }

    /**
     * Covers Expression Language RuntimeException when bad method
     */
    public function testVerificationBadMethodsCallsOk()
    {
        $trigger = $this->buildEventTriggerWithExpression(
            'wd.name() !== wi.get() and entity.ping(1) === mainEntity.pong(2)',
            EntityStub::class,
            EntityStub::class
        );

        $this->verifier->verifyTrigger($trigger);
    }

    public function testVerificationBadTypesOperandsOk()
    {
        $trigger = $this->buildEventTriggerWithExpression('wi.get()[0]', EntityStub::class, EntityStub::class);

        $this->verifier->verifyTrigger($trigger);
    }

    /**
     * @dataProvider verifyFailures
     *
     * @param string $exceptionMessage
     * @param TransitionEventTrigger $trigger
     */
    public function testVerifyTriggerException($exceptionMessage, TransitionEventTrigger $trigger)
    {
        $this->setExpectedException(
            TransitionTriggerVerifierException::class,
            $exceptionMessage
        );

        $this->verifier->verifyTrigger($trigger);
    }

    /**
     * @return array
     */
    public function verifyFailures()
    {
        return [
            'other' => [
                'Requirement field: "entity.a w < a.b" - syntax error: ' .
                '"Unexpected token "name" of value "w" around position 10."',
                $this->buildEventTriggerWithExpression('entity.a w < a.b', EntityStub::class, EntityStub::class)
            ],
            'variable' => [
                'Requirement field: "e.a < a.b" - syntax error: "Variable "e" is not valid around position 1.". ' .
                'Valid context variables are: ' .
                'wd [Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition], ' .
                'wi [Oro\Bundle\WorkflowBundle\Entity\WorkflowItem], ' .
                'entity [Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityStub], ' .
                'mainEntity [Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityStub]',
                $this->buildEventTriggerWithExpression('e.a < a.b', EntityStub::class, EntityStub::class)
            ]
        ];
    }

    /**
     * @param null|string $require
     * @param string $entity
     * @param string $workflowEntity
     * @return TransitionEventTrigger
     */
    private function buildEventTriggerWithExpression($require, $entity, $workflowEntity)
    {
        $definition = new WorkflowDefinition();
        $definition->setRelatedEntity($workflowEntity);

        $trigger = new TransitionEventTrigger();
        $trigger->setWorkflowDefinition($definition)->setEntityClass($entity)->setRequire($require);

        return $trigger;
    }
}
