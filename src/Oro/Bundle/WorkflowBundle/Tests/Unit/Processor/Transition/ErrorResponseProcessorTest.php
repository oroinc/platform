<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\ErrorResponseProcessor;
use Symfony\Component\HttpFoundation\Response;

class ErrorResponseProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ErrorResponseProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->processor = new ErrorResponseProcessor();
    }

    public function testBuildResponseFromDefinedFields()
    {
        $context = $this->createMock(TransitionContext::class);

        $context->expects(self::once())
            ->method('hasError')
            ->willReturn(true);

        $context->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(
                ['responseCode'],
                ['responseMessage']
            )
            ->willReturnOnConsecutiveCalls(418, 'message');

        $context->expects(self::once())
            ->method('setResult')
            ->with(self::callback(static function (Response $response) {
                self::assertStringContainsString('HTTP/1.0 418 message', (string) $response);

                return true;
            }));

        $context->expects(self::once())
            ->method('setProcessed')
            ->with(true);

        $this->processor->process($context);
    }

    public function testBuildResponseFromError()
    {
        $context = $this->createMock(TransitionContext::class);
        $context->expects(self::once())
            ->method('hasError')
            ->willReturn(true);

        $context->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(
                ['responseCode'],
                ['responseMessage']
            )
            ->willReturn(null);

        $context->expects(self::once())
            ->method('getError')
            ->willReturn(new \Exception('error message'));

        $context->expects(self::once())
            ->method('setResult')
            ->with(self::callback(static function (Response $response) {
                self::assertStringContainsString('HTTP/1.0 500 error message', (string) $response);

                return true;
            }));

        $context->expects(self::once())
            ->method('setProcessed')
            ->with(true);

        $this->processor->process($context);
    }

    public function testSkipHasNoErrors()
    {
        $context = $this->createMock(TransitionContext::class);
        $context->expects(self::once())
            ->method('hasError')
            ->willReturn(false);
        $context->expects(self::never())
            ->method('setResult');

        $this->processor->process($context);
    }
}
