<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Extension;

use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowMaskBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowMaskBuilderTest extends TestCase
{
    public function testViewWorkflowGroup(): void
    {
        $this->assertEquals(
            WorkflowMaskBuilder::GROUP_VIEW_WORKFLOW,
            WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_BASIC
            + WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_LOCAL
            + WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_DEEP
            + WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_GLOBAL
            + WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_SYSTEM
        );
    }

    public function testPerformTransitionsGroup(): void
    {
        $this->assertEquals(
            WorkflowMaskBuilder::GROUP_PERFORM_TRANSITIONS,
            WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_BASIC
            + WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_LOCAL
            + WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_DEEP
            + WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_GLOBAL
            + WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_SYSTEM
        );
    }

    public function testAllGroup(): void
    {
        $this->assertEquals(
            WorkflowMaskBuilder::GROUP_ALL,
            WorkflowMaskBuilder::GROUP_VIEW_WORKFLOW + WorkflowMaskBuilder::GROUP_PERFORM_TRANSITIONS
        );
    }

    public function testRemoveServiceBits(): void
    {
        $this->assertEquals(
            WorkflowMaskBuilder::REMOVE_SERVICE_BITS,
            WorkflowMaskBuilder::GROUP_ALL
        );
    }

    public function testServiceBits(): void
    {
        $this->assertEquals(
            WorkflowMaskBuilder::SERVICE_BITS,
            ~WorkflowMaskBuilder::REMOVE_SERVICE_BITS
        );
    }

    public function testGetEmptyPattern(): void
    {
        $builder = new WorkflowMaskBuilder();

        $this->assertEquals(
            '(PV) system:.. global:.. deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternViewBasic(): void
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_BASIC);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:.. local:.. basic:.V',
            $builder->getPattern()
        );
    }

    public function testGetPatternPerformBasic(): void
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_BASIC);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:.. local:.. basic:P.',
            $builder->getPattern()
        );
    }

    public function testGetPatternBasic(): void
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::GROUP_BASIC);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:.. local:.. basic:PV',
            $builder->getPattern()
        );
    }

    public function testGetPatternViewLocal(): void
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_LOCAL);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:.. local:.V basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternPerformLocal(): void
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_LOCAL);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:.. local:P. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternLocal(): void
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::GROUP_LOCAL);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:.. local:PV basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternViewDeep(): void
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_DEEP);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:.V local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternPerformDeep(): void
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_DEEP);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:P. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternDeep(): void
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::GROUP_DEEP);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:PV local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternViewGlobal(): void
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_GLOBAL);

        $this->assertEquals(
            '(PV) system:.. global:.V deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternPerformGlobal(): void
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_GLOBAL);

        $this->assertEquals(
            '(PV) system:.. global:P. deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternGlobal(): void
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::GROUP_GLOBAL);

        $this->assertEquals(
            '(PV) system:.. global:PV deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternViewSystem(): void
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_SYSTEM);

        $this->assertEquals(
            '(PV) system:.V global:.. deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternPerformSystem(): void
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_SYSTEM);

        $this->assertEquals(
            '(PV) system:P. global:.. deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternSystem(): void
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::GROUP_SYSTEM);

        $this->assertEquals(
            '(PV) system:PV global:.. deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }
}
