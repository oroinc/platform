<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Oro\Bundle\WorkflowBundle\Helper\TransitionHelper;

class TransitionHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var TransitionHelper */
    protected static $transitionHelper;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        self::$transitionHelper = new TransitionHelper();
    }

    /**
     * @dataProvider workflowEntityDataProvider
     *
     * @param object $entity
     * @param string $workflow
     * @param bool $isContains
     */
    public function testTransitionHelper($entity, $workflow, $isContains)
    {
        $this->assertSame($isContains, self::$transitionHelper->isStartedWorkflowTransition($entity, $workflow));

        $this->addWorkflowTransition($entity, $workflow);
        $this->assertTrue(self::$transitionHelper->isStartedWorkflowTransition($entity, $workflow));
        self::$transitionHelper->removeWorkflowTransition($entity, $workflow);
        $this->assertFalse(self::$transitionHelper->isStartedWorkflowTransition($entity, $workflow));

        $this->addWorkflowTransition($entity, $workflow);
    }

    /**
     * @return array
     */
    public function workflowEntityDataProvider()
    {
        $entity = new \stdClass();
        return [
            [$entity, 'workflow1', false],
            [$entity, 'workflow2', false],
            [new \stdClass(), 'workflow1', false],
            [$entity, 'workflow2', true],
        ];
    }

    /**
     * @param object $entity
     * @param string $workflow
     */
    private function addWorkflowTransition($entity, $workflow)
    {
        self::$transitionHelper->addWorkflowTransition($entity, $workflow);
    }
}
