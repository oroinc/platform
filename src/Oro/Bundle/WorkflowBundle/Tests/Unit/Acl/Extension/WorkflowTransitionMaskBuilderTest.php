<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Extension;

use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowTransitionMaskBuilder;

class WorkflowTransitionMaskBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testPerformTransitionGroup()
    {
        $this->assertEquals(
            WorkflowTransitionMaskBuilder::GROUP_PERFORM_TRANSITION,
            WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_BASIC
            + WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_LOCAL
            + WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_DEEP
            + WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_GLOBAL
            + WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_SYSTEM
        );
    }

    public function testAllGroup()
    {
        $this->assertEquals(
            WorkflowTransitionMaskBuilder::GROUP_ALL,
            WorkflowTransitionMaskBuilder::GROUP_PERFORM_TRANSITION
        );
    }

    public function testRemoveServiceBits()
    {
        $this->assertEquals(
            WorkflowTransitionMaskBuilder::REMOVE_SERVICE_BITS,
            WorkflowTransitionMaskBuilder::GROUP_ALL
        );
    }

    public function testServiceBits()
    {
        $this->assertEquals(
            WorkflowTransitionMaskBuilder::SERVICE_BITS,
            ~WorkflowTransitionMaskBuilder::REMOVE_SERVICE_BITS
        );
    }

    public function testGetEmptyPattern()
    {
        $builder = new WorkflowTransitionMaskBuilder();

        $this->assertEquals(
            '(P) system:. global:. deep:. local:. basic:.',
            $builder->getPattern()
        );
    }

    public function testGetPatternBasic()
    {
        $builder = new WorkflowTransitionMaskBuilder();
        $builder->add(WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_BASIC);

        $this->assertEquals(
            '(P) system:. global:. deep:. local:. basic:P',
            $builder->getPattern()
        );
    }

    public function testGetPatternLocal()
    {
        $builder = new WorkflowTransitionMaskBuilder();
        $builder->add(WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_LOCAL);

        $this->assertEquals(
            '(P) system:. global:. deep:. local:P basic:.',
            $builder->getPattern()
        );
    }

    public function testGetPatternDeep()
    {
        $builder = new WorkflowTransitionMaskBuilder();
        $builder->add(WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_DEEP);

        $this->assertEquals(
            '(P) system:. global:. deep:P local:. basic:.',
            $builder->getPattern()
        );
    }

    public function testGetPatternGlobal()
    {
        $builder = new WorkflowTransitionMaskBuilder();
        $builder->add(WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_GLOBAL);

        $this->assertEquals(
            '(P) system:. global:P deep:. local:. basic:.',
            $builder->getPattern()
        );
    }

    public function testGetPatternSystem()
    {
        $builder = new WorkflowTransitionMaskBuilder();
        $builder->add(WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_SYSTEM);

        $this->assertEquals(
            '(P) system:P global:. deep:. local:. basic:.',
            $builder->getPattern()
        );
    }
}
