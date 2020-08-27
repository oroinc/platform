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
    /** @var \PHPUnit\Framework\MockObject\MockObject|RequestStack */
    private $requestStack;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
    }

    public function testGetViewWhenRequestStackIsEmpty()
    {
        $this->requestStack->expects(self::exactly(2))
            ->method('getMasterRequest')
            ->willReturn(null);

        $docViewDetector = new RestDocViewDetector($this->requestStack, []);

        self::assertSame('', $docViewDetector->getView());
        // test that the view is not cached
        self::assertSame('', $docViewDetector->getView());
    }

    public function testGetViewWhenRequestDoesNotContainViewAttribute()
    {
        $request = Request::create('url');

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);

        $docViewDetector = new RestDocViewDetector($this->requestStack, []);

        self::assertSame('', $docViewDetector->getView());
        // test that the view is cached
        self::assertSame('', $docViewDetector->getView());
    }

    public function testGetViewWhenRequestContainsViewAttribute()
    {
        $view = 'test';
        $request = Request::create('url');
        $request->attributes->set('view', $view);

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);

        $docViewDetector = new RestDocViewDetector($this->requestStack, []);

        self::assertSame($view, $docViewDetector->getView());
        // test that the view is cached
        self::assertSame($view, $docViewDetector->getView());
    }

    public function testSetView()
    {
        $view = 'test';

        $docViewDetector = new RestDocViewDetector($this->requestStack, []);
        $docViewDetector->setView($view);

        $this->requestStack->expects(self::never())
            ->method('getMasterRequest');

        self::assertEquals($view, $docViewDetector->getView());
    }

    public function testGetRequestTypeWhenNoProviderThatCanDetectRequestType()
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

    public function testGetRequestTypeWhenProviderDetectsRequestType()
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

    public function testShouldClearRequestTypeWhenViewChanged()
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

    public function testShouldInitializeRequestTypeProvider()
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

    public function testShouldReinitializeRequestTypeProviderAfterReset()
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

    public function testGetVersionIfItWasNotSetExplicitly()
    {
        $docViewDetector = new RestDocViewDetector($this->requestStack, []);
        self::assertEquals('latest', $docViewDetector->getVersion());
    }

    public function testSetVersion()
    {
        $version = '1.2';

        $docViewDetector = new RestDocViewDetector($this->requestStack, []);
        $docViewDetector->setVersion($version);

        self::assertEquals($version, $docViewDetector->getVersion());
    }

    public function testShouldClearVersionWhenViewChanged()
    {
        $docViewDetector = new RestDocViewDetector($this->requestStack, []);
        $docViewDetector->setVersion('1.2');
        $docViewDetector->setView();

        self::assertEquals('latest', $docViewDetector->getVersion());
    }
}
