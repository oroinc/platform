<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Import\WorkflowImportProcessor;
use Oro\Bundle\WorkflowBundle\Configuration\Import\WorkflowImportProcessorSupervisor;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Import\Stub\StubWorkflowImportCallbackProcessor;

class WorkflowImportProcessorSupervisorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WorkflowImportProcessorSupervisor
     */
    private $processor;

    protected function setUp()
    {
        $this->processor = new WorkflowImportProcessorSupervisor();
    }

    public function testProcessProxyParent()
    {
        /** @var ConfigImportProcessorInterface|\PHPUnit\Framework\MockObject\MockObject $parent */
        $parent = $this->createMock(ConfigImportProcessorInterface::class);

        $first = $this->createImportProcessorMock('targetA', 'resourceA', ['setParent', 'process']);
        $second = $this->createImportProcessorMock('targetB', 'resourceB', ['setParent', 'process']);

        $content = ['content'];
        $file = new \SplFileInfo(__FILE__);

        $this->processor->addImportProcessor($first);
        $this->processor->addImportProcessor($second);
        $this->processor->setParent($parent);

        $first->expects($this->once())->method('setParent')->with($parent);
        $first->expects($this->once())->method('process')->with($content, $file);

        $this->processor->process($content, $file);

        $second->expects($this->once())->method('setParent')->with($parent);
        $second->expects($this->once())->method('process')->with($content, $file);
        $this->processor->process($content, $file);
    }

    public function testSkipProcessed()
    {
        $once = $this->createImportProcessorMock('targetA', 'resourceA', ['setParent', 'process']);

        $content = ['content'];
        $file = new \SplFileInfo(__FILE__);

        $this->processor->addImportProcessor($once);
        $this->processor->addImportProcessor($once);
        $this->processor->addImportProcessor($once);

        $once->expects($this->once())->method('process')->with($content, $file);

        $this->processor->process($content, $file);
        $this->processor->process($content, $file);
        $this->processor->process($content, $file);
    }

    public function testRecursionPreventionStack()
    {
        $fromA = $this->createCallbackProcessor(
            function ($content) {
                $content['data'] .= '[A->B]';

                return $content;
            },
            'B',
            'A'
        );

        $fromB = $this->createCallbackProcessor(
            function ($content) {
                $content['data'] .= '[B->A]';

                return $content;
            },
            'A',
            'B'
        );

        $parent = new StubWorkflowImportCallbackProcessor(
            function ($content, $file) {
                return $this->processor->process($content, $file);
            }
        );

        $content = ['data' => ''];
        $file = new \SplFileInfo(__FILE__);
        $this->processor->addImportProcessor($fromA);
        $this->processor->addImportProcessor($fromB);
        $this->processor->setParent($parent);
        $processed = $this->processor->process($content, $file);

        $this->assertEquals(['data' => '[B->A][A->B]'], $processed);
    }

    public function testRecursionPreventionStackComplex()
    {
        $fromA = $this->createCallbackProcessor(
            function ($content) {
                $content['data'] .= '[A->B]';

                return $content;
            },
            'B',
            'A'
        );

        $fromC = $this->createCallbackProcessor(
            function ($content) {
                $content['data'] .= '[C->A]';

                return $content;
            },
            'A',
            'C'
        );

        $fromB = $this->createCallbackProcessor(
            function ($content) {
                $content['data'] .= '[B->C]';

                return $content;
            },
            'C',
            'B'
        );

        $parent = new StubWorkflowImportCallbackProcessor(
            function ($content, $file) {
                return $this->processor->process($content, $file);
            }
        );

        $content = ['data' => ''];
        $file = new \SplFileInfo(__FILE__);
        $this->processor->addImportProcessor($fromA);
        $this->processor->addImportProcessor($fromC);
        $this->processor->addImportProcessor($fromB);
        $this->processor->setParent($parent);
        $processed = $this->processor->process($content, $file);

        $this->assertEquals(['data' => '[B->C][C->A][A->B]'], $processed);
    }

    public function testOuterProcessingLoopCircularReferenceException()
    {
        $fromA = $this->createCallbackProcessor(
            function ($content) {
                $content['data'] .= '[A->B]';

                return $content;
            },
            'B',
            'A'
        );

        $fromB = $this->createCallbackProcessor(
            function ($content) {
                $content['data'] .= '[B->A]';

                return $content;
            },
            'A',
            'B'
        );

        $parent = new StubWorkflowImportCallbackProcessor(
            function ($content, $file) {
                return $this->processor->process($content, $file);
            }
        );

        $content = ['data' => ''];
        $file = new \SplFileInfo(__FILE__);
        $this->processor->addImportProcessor($fromA);
        $this->processor->addImportProcessor($fromB);
        $this->processor->setParent($parent);

        $fromB->setParent($parent);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageRegExp(
            '/Recursion met\. ' .
            'File `([^`]+)` tries to import workflow `B` for `A` that currently imports it too in `([^`]+)`/'
        );

        $result = $fromB->process($content, $file); //outer call. not unstacked

        $this->assertEquals(['data' => 'cas'], $result);
    }

    /**
     * @param string $target
     * @param string $resource
     * @param array $methods
     * @return WorkflowImportProcessor|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createImportProcessorMock(string $target, string $resource, array $methods = [])
    {
        $builder = $this->getMockBuilder(WorkflowImportProcessor::class);
        $builder->disableOriginalConstructor();

        foreach (['getTarget', 'getResource'] as $method) {
            if (!in_array($method, $methods, true)) {
                $methods[] = $method;
            }
        }

        $builder->setMethods($methods);

        $workflowImportProcessor = $builder->getMock();
        $workflowImportProcessor->expects($this->any())->method('getTarget')->willReturn($target);
        $workflowImportProcessor->expects($this->any())->method('getResource')->willReturn($resource);

        return $workflowImportProcessor;
    }

    /**
     * @param callable $processCallback
     * @param string $target
     * @param string $resource
     * @return StubWorkflowImportCallbackProcessor
     */
    private function createCallbackProcessor(
        callable $processCallback,
        string $target,
        string $resource
    ): Stub\StubWorkflowImportCallbackProcessor {
        $processor = new StubWorkflowImportCallbackProcessor($processCallback);
        $processor->setTarget($target);
        $processor->setResource($resource);

        return $processor;
    }
}
