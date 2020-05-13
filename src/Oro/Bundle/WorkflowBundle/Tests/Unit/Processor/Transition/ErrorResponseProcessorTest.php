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

        $context->expects(static::once())->method('hasError')->willReturn(true);

        $context->expects(static::exactly(2))
            ->method('get')
            ->withConsecutive(
                ['responseCode'],
                ['responseMessage']
            )
            ->willReturnOnConsecutiveCalls(418, 'message');

        $context->expects(static::once())
            ->method('setResult')
            ->with(static::callback(static function (Response $response) {
                static::assertStringContainsString("HTTP/1.0 418 message", (string) $response);

                return true;
            }));

        $context->expects(static::once())->method('setProcessed')->with(true);

        $this->processor->process($context);
    }

    public function testBuildResponseFromError()
    {
        /** @var TransitionContext|MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects(static::once())->method('hasError')->willReturn(true);

        $context->expects(static::exactly(2))
            ->method('get')
            ->withConsecutive(
                ['responseCode'],
                ['responseMessage']
            )
            ->willReturn(null);

        $context->expects(static::once())->method('getError')->willReturn(new \Exception('error message'));

        $context->expects(static::once())
            ->method('setResult')
            ->with(static::callback(static function (Response $response) {
                static::assertStringContainsString("HTTP/1.0 500 error message", (string) $response);

                return true;
            }));

        $context->expects(static::once())->method('setProcessed')->with(true);

        $this->processor->process($context);
    }

    public function testSkipHasNoErrors()
    {
        /** @var TransitionContext|MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects(static::once())->method('hasError')->willReturn(false);
        $context->expects(static::never())->method('setResult');

        $this->processor->process($context);
    }
}
