<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Extension;

use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowTransitionMaskBuilder;
use PHPUnit\Framework\TestCase;

class WorkflowTransitionMaskBuilderTest extends TestCase
{
    public function testPerformTransitionGroup(): void
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

    public function testAllGroup(): void
    {
        $this->assertEquals(
            WorkflowTransitionMaskBuilder::GROUP_ALL,
            WorkflowTransitionMaskBuilder::GROUP_PERFORM_TRANSITION
        );
    }

    public function testRemoveServiceBits(): void
    {
        $this->assertEquals(
            WorkflowTransitionMaskBuilder::REMOVE_SERVICE_BITS,
            WorkflowTransitionMaskBuilder::GROUP_ALL
        );
    }

    public function testServiceBits(): void
    {
        $this->assertEquals(
            WorkflowTransitionMaskBuilder::SERVICE_BITS,
            ~WorkflowTransitionMaskBuilder::REMOVE_SERVICE_BITS
        );
    }

    public function testGetEmptyPattern(): void
    {
        $builder = new WorkflowTransitionMaskBuilder();

        $this->assertEquals(
            '(P) system:. global:. deep:. local:. basic:.',
            $builder->getPattern()
        );
    }

    public function testGetPatternBasic(): void
    {
        $builder = new WorkflowTransitionMaskBuilder();
        $builder->add(WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_BASIC);

        $this->assertEquals(
            '(P) system:. global:. deep:. local:. basic:P',
            $builder->getPattern()
        );
    }

    public function testGetPatternLocal(): void
    {
        $builder = new WorkflowTransitionMaskBuilder();
        $builder->add(WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_LOCAL);

        $this->assertEquals(
            '(P) system:. global:. deep:. local:P basic:.',
            $builder->getPattern()
        );
    }

    public function testGetPatternDeep(): void
    {
        $builder = new WorkflowTransitionMaskBuilder();
        $builder->add(WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_DEEP);

        $this->assertEquals(
            '(P) system:. global:. deep:P local:. basic:.',
            $builder->getPattern()
        );
    }

    public function testGetPatternGlobal(): void
    {
        $builder = new WorkflowTransitionMaskBuilder();
        $builder->add(WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_GLOBAL);

        $this->assertEquals(
            '(P) system:. global:P deep:. local:. basic:.',
            $builder->getPattern()
        );
    }

    public function testGetPatternSystem(): void
    {
        $builder = new WorkflowTransitionMaskBuilder();
        $builder->add(WorkflowTransitionMaskBuilder::MASK_PERFORM_TRANSITION_SYSTEM);

        $this->assertEquals(
            '(P) system:P global:. deep:. local:. basic:.',
            $builder->getPattern()
        );
    }
}
