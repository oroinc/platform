<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\UnknownAttributeException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\ErrorNormalizeProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Suppressing for stubs and mock classes
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ErrorNormalizeProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var  LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /** @var  ErrorNormalizeProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new ErrorNormalizeProcessor($this->logger);
    }

    /**
     * @dataProvider errorsProvider
     *
     * @param \Throwable $error
     * @param int $code
     * @param string $message
     */
    public function testMessagesAndCodesCatch(\Throwable $error, int $code, string $message)
    {
        $context = $this->createContextAndLoggingAssertions($error);

        $this->processor->process($context);

        $this->assertEquals($code, $context->get('responseCode'));
        $this->assertEquals($message, $context->get('responseMessage'));
    }

    /**
     * @param \Throwable $error
     * @return TransitionContext
     */
    protected function createContextAndLoggingAssertions(\Throwable $error): TransitionContext
    {
        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        $transition = $this->createMock(Transition::class);

        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn('test_workflow');

        $context = new TransitionContext();
        $context->setError($error);
        $context->setTransition($transition);
        $context->setWorkflowItem($workflowItem);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                '[TransitionHandler] Could not perform transition.',
                [
                    'exception' => $error,
                    'transition' => $transition,
                    'workflowItem' => $workflowItem
                ]
            );

        return $context;
    }

    /**
     * @return \Generator
     */
    public function errorsProvider()
    {
        yield 'http' => [
            new BadRequestHttpException('http message', null, 418),
            400, //has own status code regardless of argument
            'http message'
        ];

        yield 'http threats as code aware' => [
            new HttpException(418, 'I am a tea pot!! >:D'),
            418, //has own status code regardless of argument
            'I am a tea pot!! >:D'
        ];

        yield 'workflow 404' => [
            new WorkflowNotFoundException('ghosty workflow', 429),
            404,//as usual
            'Workflow "ghosty workflow" not found'
        ];

        yield 'attribute unknown' => [
            new UnknownAttributeException('attribute?', 42),
            400,//bad request
            'attribute?'
        ];

        yield 'transition invalid' => [
            new InvalidTransitionException('message about that', 100500),
            400,
            'message about that'
        ];

        yield 'forbidden transition' => [
            new ForbiddenTransitionException('not allowed', 2),
            403, //http forbidden
            'not allowed'
        ];

        yield 'any other problem that can be thrown' => [
            new \Error('your strict isn\'t strict', 314),
            500,
            'your strict isn\'t strict'
        ];
    }

    public function testSkipHasNoError()
    {
        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())->method('hasError')->willReturn(false);
        $context->expects($this->never())->method('getError');

        $this->processor->process($context);
    }

    public function testSkipAsResponseCodeAndMessageAlreadyDefined()
    {
        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())->method('hasError')->willReturn(true);
        $context->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive(['responseCode'], ['responseMessage'])
            ->willReturn(true);

        $context->expects($this->never())->method('getError');

        $this->processor->process($context);
    }
}
