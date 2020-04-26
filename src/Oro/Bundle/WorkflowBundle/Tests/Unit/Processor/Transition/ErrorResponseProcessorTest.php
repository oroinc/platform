<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\ErrorResponseProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

class ErrorResponseProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ErrorResponseProcessor */
    protected $processor;

    protected function setUp(): void
    {
        $this->processor = new ErrorResponseProcessor();
    }

    public function testBuildResponseFromDefinedFields()
    {
        /** @var TransitionContext|MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('hasError')
            ->willReturn(true);

        $context->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['responseCode'], ['responseMessage'])
            ->willReturnOnConsecutiveCalls(418, 'message');

        $context->expects(static::once())
            ->method('setResult')
            ->with(static::callback(static function (Response $response) {
                return static::stringContains("HTTP/1.0 418 message")->evaluate((string) $response);
            }));
        $context->expects($this->once())->method('setProcessed')->with(true);

        $this->processor->process($context);
    }

    public function testBuildResponseFromError()
    {
        /** @var TransitionContext|MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('hasError')
            ->willReturn(true);

        $context->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['responseCode'], ['responseMessage'])
            ->willReturn(null);

        $context->expects($this->once())->method('getError')->willReturn(new \Exception('error message'));
        $context->expects(static::once())
            ->method('setResult')
            ->with(static::callback(static function (Response $response) {
                return static::stringContains("HTTP/1.0 500 error message")->evaluate((string) $response);
            }));

        $context->expects($this->once())->method('setProcessed')->with(true);

        $this->processor->process($context);
    }

    public function testSkipHasNoErrors()
    {
        /** @var TransitionContext|MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())->method('hasError')->willReturn(false);
        $context->expects($this->never())->method('setResult');

        $this->processor->process($context);
    }
}
