<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Extension;

use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowMaskBuilder;

class WorkflowMaskBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testViewWorkflowGroup()
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

    public function testPerformTransitionsGroup()
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

    public function testAllGroup()
    {
        $this->assertEquals(
            WorkflowMaskBuilder::GROUP_ALL,
            WorkflowMaskBuilder::GROUP_VIEW_WORKFLOW + WorkflowMaskBuilder::GROUP_PERFORM_TRANSITIONS
        );
    }

    public function testRemoveServiceBits()
    {
        $this->assertEquals(
            WorkflowMaskBuilder::REMOVE_SERVICE_BITS,
            WorkflowMaskBuilder::GROUP_ALL
        );
    }

    public function testServiceBits()
    {
        $this->assertEquals(
            WorkflowMaskBuilder::SERVICE_BITS,
            ~WorkflowMaskBuilder::REMOVE_SERVICE_BITS
        );
    }

    public function testGetEmptyPattern()
    {
        $builder = new WorkflowMaskBuilder();

        $this->assertEquals(
            '(PV) system:.. global:.. deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternViewBasic()
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_BASIC);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:.. local:.. basic:.V',
            $builder->getPattern()
        );
    }

    public function testGetPatternPerformBasic()
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_BASIC);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:.. local:.. basic:P.',
            $builder->getPattern()
        );
    }

    public function testGetPatternBasic()
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::GROUP_BASIC);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:.. local:.. basic:PV',
            $builder->getPattern()
        );
    }

    public function testGetPatternViewLocal()
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_LOCAL);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:.. local:.V basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternPerformLocal()
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_LOCAL);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:.. local:P. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternLocal()
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::GROUP_LOCAL);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:.. local:PV basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternViewDeep()
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_DEEP);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:.V local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternPerformDeep()
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_DEEP);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:P. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternDeep()
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::GROUP_DEEP);

        $this->assertEquals(
            '(PV) system:.. global:.. deep:PV local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternViewGlobal()
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_GLOBAL);

        $this->assertEquals(
            '(PV) system:.. global:.V deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternPerformGlobal()
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_GLOBAL);

        $this->assertEquals(
            '(PV) system:.. global:P. deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternGlobal()
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::GROUP_GLOBAL);

        $this->assertEquals(
            '(PV) system:.. global:PV deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternViewSystem()
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_VIEW_WORKFLOW_SYSTEM);

        $this->assertEquals(
            '(PV) system:.V global:.. deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternPerformSystem()
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::MASK_PERFORM_TRANSITIONS_SYSTEM);

        $this->assertEquals(
            '(PV) system:P. global:.. deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }

    public function testGetPatternSystem()
    {
        $builder = new WorkflowMaskBuilder();
        $builder->add(WorkflowMaskBuilder::GROUP_SYSTEM);

        $this->assertEquals(
            '(PV) system:PV global:.. deep:.. local:.. basic:..',
            $builder->getPattern()
        );
    }
}
