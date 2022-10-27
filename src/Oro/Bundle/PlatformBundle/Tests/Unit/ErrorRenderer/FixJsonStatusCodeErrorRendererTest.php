<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\ErrorRenderer;

use Oro\Bundle\PlatformBundle\ErrorRenderer\FixJsonStatusCodeErrorRenderer;
use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FixJsonStatusCodeErrorRendererTest extends \PHPUnit\Framework\TestCase
{
    /** @var ErrorRendererInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerErrorRenderer;

    /** @var FixJsonStatusCodeErrorRenderer */
    private $errorRenderer;

    protected function setUp(): void
    {
        $this->innerErrorRenderer = $this->createMock(ErrorRendererInterface::class);

        $this->errorRenderer = new FixJsonStatusCodeErrorRenderer(
            $this->innerErrorRenderer,
            ['application/json']
        );
    }

    private function expectsInnerErrorRendererRender(
        \Throwable $exception,
        array $headers = [],
        ?string $responseBody = null
    ): FlattenException {
        $flattenException = FlattenException::createFromThrowable($exception);
        $flattenException->setStatusText(Response::$statusTexts[$flattenException->getStatusCode()]);
        $flattenException->setHeaders(array_replace($flattenException->getHeaders(), $headers));
        if (null !== $responseBody) {
            $flattenException->setAsString($responseBody);
        }

        $this->innerErrorRenderer->expects(self::once())
            ->method('render')
            ->with(self::identicalTo($exception))
            ->willReturn($flattenException);

        return $flattenException;
    }

    public function testRenderForUnknownResponseType(): void
    {
        $exception = new NotFoundHttpException();
        $flattenException = $this->expectsInnerErrorRendererRender($exception);

        self::assertSame($flattenException, $this->errorRenderer->render($exception));
        self::assertSame(404, $flattenException->getStatusCode());
        self::assertEquals(Response::$statusTexts[404], $flattenException->getStatusText());
    }

    public function testRenderForNotJsonResponse(): void
    {
        $exception = new NotFoundHttpException();
        $flattenException = $this->expectsInnerErrorRendererRender(
            $exception,
            ['Content-Type' => 'text/html']
        );

        self::assertSame($flattenException, $this->errorRenderer->render($exception));
        self::assertSame(404, $flattenException->getStatusCode());
        self::assertEquals(Response::$statusTexts[404], $flattenException->getStatusText());
    }

    public function testRenderForJsonResponseWithoutBody(): void
    {
        $exception = new NotFoundHttpException();
        $flattenException = $this->expectsInnerErrorRendererRender(
            $exception,
            ['Content-Type' => 'application/json']
        );

        self::assertSame($flattenException, $this->errorRenderer->render($exception));
        self::assertSame(404, $flattenException->getStatusCode());
        self::assertEquals(Response::$statusTexts[404], $flattenException->getStatusText());
    }

    public function testRenderForJsonResponseWhenCodeInBodyEqualsToStatusCode(): void
    {
        $exception = new NotFoundHttpException();
        $flattenException = $this->expectsInnerErrorRendererRender(
            $exception,
            ['Content-Type' => 'application/json'],
            json_encode(['code' => 404], JSON_THROW_ON_ERROR)
        );

        self::assertSame($flattenException, $this->errorRenderer->render($exception));
        self::assertSame(404, $flattenException->getStatusCode());
        self::assertEquals(Response::$statusTexts[404], $flattenException->getStatusText());
    }

    public function testRenderForJsonResponseWhenCodeInBodyDoesNotEqualToStatusCode(): void
    {
        $exception = new NotFoundHttpException();
        $flattenException = $this->expectsInnerErrorRendererRender(
            $exception,
            ['Content-Type' => 'application/json'],
            json_encode(['code' => 400], JSON_THROW_ON_ERROR)
        );

        self::assertSame($flattenException, $this->errorRenderer->render($exception));
        self::assertSame(400, $flattenException->getStatusCode());
        self::assertEquals(Response::$statusTexts[400], $flattenException->getStatusText());
    }

    public function testRenderForJsonResponseWhenCodeInBodyIsNotValidStatusCode(): void
    {
        $exception = new NotFoundHttpException();
        $flattenException = $this->expectsInnerErrorRendererRender(
            $exception,
            ['Content-Type' => 'application/json'],
            json_encode(['code' => 1], JSON_THROW_ON_ERROR)
        );

        self::assertSame($flattenException, $this->errorRenderer->render($exception));
        self::assertSame(404, $flattenException->getStatusCode());
        self::assertEquals(Response::$statusTexts[404], $flattenException->getStatusText());
    }

    public function testRenderForJsonResponseWhenCodeInBodyDoesNotEqualToStatusCodeAndItIsNumericString(): void
    {
        $exception = new NotFoundHttpException();
        $flattenException = $this->expectsInnerErrorRendererRender(
            $exception,
            ['Content-Type' => 'application/json'],
            json_encode(['code' => '400'], JSON_THROW_ON_ERROR)
        );

        self::assertSame($flattenException, $this->errorRenderer->render($exception));
        self::assertSame(400, $flattenException->getStatusCode());
        self::assertEquals(Response::$statusTexts[400], $flattenException->getStatusText());
    }

    public function testRenderForJsonResponseWhenCodeInBodyDoesNotEqualToStatusCodeAndItIsNotNumericString(): void
    {
        $exception = new NotFoundHttpException();
        $flattenException = $this->expectsInnerErrorRendererRender(
            $exception,
            ['Content-Type' => 'application/json'],
            json_encode(['code' => 'test'], JSON_THROW_ON_ERROR)
        );

        self::assertSame($flattenException, $this->errorRenderer->render($exception));
        self::assertSame(404, $flattenException->getStatusCode());
        self::assertEquals(Response::$statusTexts[404], $flattenException->getStatusText());
    }

    public function testRenderForJsonResponseWhenCodeInBodyDoesNotEqualToStatusCodeAndItIsFloatNumericString(): void
    {
        $exception = new NotFoundHttpException();
        $flattenException = $this->expectsInnerErrorRendererRender(
            $exception,
            ['Content-Type' => 'application/json'],
            json_encode(['code' => '400.0'], JSON_THROW_ON_ERROR)
        );

        self::assertSame($flattenException, $this->errorRenderer->render($exception));
        self::assertSame(404, $flattenException->getStatusCode());
        self::assertEquals(Response::$statusTexts[404], $flattenException->getStatusText());
    }

    public function testRenderForJsonResponseWhenBodyIsInvalidJson(): void
    {
        $exception = new NotFoundHttpException();
        $flattenException = $this->expectsInnerErrorRendererRender(
            $exception,
            ['Content-Type' => 'application/json'],
            'invalid json value'
        );

        self::assertSame($flattenException, $this->errorRenderer->render($exception));
        self::assertSame(404, $flattenException->getStatusCode());
        self::assertEquals(Response::$statusTexts[404], $flattenException->getStatusText());
    }
}
