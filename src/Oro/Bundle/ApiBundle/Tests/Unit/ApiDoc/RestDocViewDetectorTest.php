<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\RequestTypeProviderInterface;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\ApiDoc\RestRequestTypeProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RestDocViewDetectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
    }

    public function testGetViewWhenRequestStackIsEmpty(): void
    {
        $this->requestStack->expects(self::exactly(2))
            ->method('getMainRequest')
            ->willReturn(null);

        $docViewDetector = new RestDocViewDetector($this->requestStack, []);

        self::assertSame('', $docViewDetector->getView());
        // test that the view is not cached
        self::assertSame('', $docViewDetector->getView());
    }

    public function testGetViewWhenRequestDoesNotContainViewAttribute(): void
    {
        $request = Request::create('url');

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        $docViewDetector = new RestDocViewDetector($this->requestStack, []);

        self::assertSame('', $docViewDetector->getView());
        // test that the view is cached
        self::assertSame('', $docViewDetector->getView());
    }

    public function testGetViewWhenRequestContainsViewAttribute(): void
    {
        $view = 'test';
        $request = Request::create('url');
        $request->attributes->set('view', $view);

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        $docViewDetector = new RestDocViewDetector($this->requestStack, []);

        self::assertSame($view, $docViewDetector->getView());
        // test that the view is cached
        self::assertSame($view, $docViewDetector->getView());
    }

    public function testSetView(): void
    {
        $view = 'test';

        $docViewDetector = new RestDocViewDetector($this->requestStack, []);
        $docViewDetector->setView($view);

        $this->requestStack->expects(self::never())
            ->method('getMainRequest');

        self::assertEquals($view, $docViewDetector->getView());
    }

    public function testGetRequestTypeWhenNoProviderThatCanDetectRequestType(): void
    {
        $requestTypeProvider = $this->createMock(RequestTypeProviderInterface::class);
        $docViewDetector = new RestDocViewDetector($this->requestStack, [$requestTypeProvider]);

        $requestTypeProvider->expects(self::once())
            ->method('getRequestType')
            ->willReturn(null);

        $requestType = $docViewDetector->getRequestType();
        self::assertInstanceOf(RequestType::class, $requestType);
        self::assertTrue($requestType->isEmpty());

        // test that the request type is cached
        $requestType = $docViewDetector->getRequestType();
        self::assertInstanceOf(RequestType::class, $requestType);
        self::assertTrue($requestType->isEmpty());
    }

    public function testGetRequestTypeWhenProviderDetectsRequestType(): void
    {
        $requestTypeProvider = $this->createMock(RequestTypeProviderInterface::class);
        $docViewDetector = new RestDocViewDetector($this->requestStack, [$requestTypeProvider]);

        $requestTypeProvider->expects(self::once())
            ->method('getRequestType')
            ->willReturn(new RequestType(['test']));

        $requestType = $docViewDetector->getRequestType();
        self::assertInstanceOf(RequestType::class, $requestType);
        self::assertTrue($requestType->contains('test'));

        // test that the request type is cached
        $requestType = $docViewDetector->getRequestType();
        self::assertInstanceOf(RequestType::class, $requestType);
        self::assertTrue($requestType->contains('test'));
    }

    public function testShouldClearRequestTypeWhenViewChanged(): void
    {
        $requestTypeProvider = $this->createMock(RequestTypeProviderInterface::class);
        $docViewDetector = new RestDocViewDetector($this->requestStack, [$requestTypeProvider]);

        $requestTypeProvider->expects(self::exactly(2))
            ->method('getRequestType')
            ->willReturn(null);

        $docViewDetector->getRequestType();

        $docViewDetector->setView();
        $docViewDetector->getRequestType();
    }

    public function testShouldInitializeRequestTypeProvider(): void
    {
        $requestTypeProvider = $this->createMock(RestRequestTypeProvider::class);
        $docViewDetector = new RestDocViewDetector($this->requestStack, [$requestTypeProvider]);

        $requestTypeProvider->expects(self::once())
            ->method('setRestDocViewDetector')
            ->with(self::identicalTo($docViewDetector));
        $requestTypeProvider->expects(self::exactly(2))
            ->method('getRequestType')
            ->willReturn(null);

        $docViewDetector->getRequestType();
        $docViewDetector->setView();
        $docViewDetector->getRequestType();
    }

    public function testShouldReinitializeRequestTypeProviderAfterReset(): void
    {
        $requestTypeProvider = $this->createMock(RestRequestTypeProvider::class);
        $docViewDetector = new RestDocViewDetector($this->requestStack, [$requestTypeProvider]);

        $requestTypeProvider->expects(self::exactly(2))
            ->method('setRestDocViewDetector')
            ->with(self::identicalTo($docViewDetector));
        $requestTypeProvider->expects(self::exactly(2))
            ->method('getRequestType')
            ->willReturn(null);

        $docViewDetector->getRequestType();
        $docViewDetector->reset();
        $docViewDetector->getRequestType();
    }

    public function testGetVersionIfItWasNotSetExplicitly(): void
    {
        $docViewDetector = new RestDocViewDetector($this->requestStack, []);
        self::assertEquals('latest', $docViewDetector->getVersion());
    }

    public function testSetVersion(): void
    {
        $version = '1.2';

        $docViewDetector = new RestDocViewDetector($this->requestStack, []);
        $docViewDetector->setVersion($version);

        self::assertEquals($version, $docViewDetector->getVersion());
    }

    public function testShouldClearVersionWhenViewChanged(): void
    {
        $docViewDetector = new RestDocViewDetector($this->requestStack, []);
        $docViewDetector->setVersion('1.2');
        $docViewDetector->setView();

        self::assertEquals('latest', $docViewDetector->getVersion());
    }
}
