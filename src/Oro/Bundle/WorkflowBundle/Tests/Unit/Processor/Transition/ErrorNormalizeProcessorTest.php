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
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ErrorNormalizeProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ErrorNormalizeProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new ErrorNormalizeProcessor($this->logger);
    }

    /**
     * @dataProvider errorsProvider
     */
    public function testMessagesAndCodesCatch(\Throwable $error, int $code, string $message)
    {
        $context = $this->createContextAndLoggingAssertions($error);

        $this->processor->process($context);

        $this->assertEquals($code, $context->get('responseCode'));
        $this->assertEquals($message, $context->get('responseMessage'));
    }

    private function createContextAndLoggingAssertions(\Throwable $error): TransitionContext
    {
        $transition = $this->createMock(Transition::class);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');

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

    public function errorsProvider(): array
    {
        return [
            'http' => [
                new BadRequestHttpException('http message', null, 418),
                400, //has own status code regardless of argument
                'http message'
            ],
            'http threats as code aware' => [
                new HttpException(418, 'I am a tea pot!! >:D'),
                418, //has own status code regardless of argument
                'I am a tea pot!! >:D'
            ],
            'workflow 404' => [
                new WorkflowNotFoundException('ghosty workflow'),
                404,//as usual
                'Workflow "ghosty workflow" not found'
            ],
            'attribute unknown' => [
                new UnknownAttributeException('attribute?', 42),
                400,//bad request
                'attribute?'
            ],
            'transition invalid' => [
                new InvalidTransitionException('message about that', 100500),
                400,
                'message about that'
            ],
            'forbidden transition' => [
                new ForbiddenTransitionException('not allowed', 2),
                403, //http forbidden
                'not allowed'
            ],
            'any other problem that can be thrown' => [
                new \Error('your strict isn\'t strict', 314),
                500,
                'your strict isn\'t strict'
            ]
        ];
    }

    public function testSkipHasNoError()
    {
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('hasError')
            ->willReturn(false);
        $context->expects($this->never())
            ->method('getError');

        $this->processor->process($context);
    }

    public function testSkipAsResponseCodeAndMessageAlreadyDefined()
    {
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('hasError')
            ->willReturn(true);
        $context->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive(['responseCode'], ['responseMessage'])
            ->willReturn(true);

        $context->expects($this->never())
            ->method('getError');

        $this->processor->process($context);
    }
}
