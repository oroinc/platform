<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\RequestTypeProviderInterface;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RestDocViewDetectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|RequestStack */
    private $requestStack;

    /** @var RestDocViewDetector */
    private $docViewDetector;

    protected function setUp()
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->docViewDetector = new RestDocViewDetector($this->requestStack);
    }

    public function testGetViewWhenRequestStackIsEmpty()
    {
        $this->requestStack->expects(self::exactly(2))
            ->method('getMasterRequest')
            ->willReturn(null);

        self::assertSame('', $this->docViewDetector->getView());
        // test that the view is not cached
        self::assertSame('', $this->docViewDetector->getView());
    }

    public function testGetViewWhenRequestDoesNotContainViewAttribute()
    {
        $request = Request::create('url');

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);

        self::assertSame('', $this->docViewDetector->getView());
        // test that the view is cached
        self::assertSame('', $this->docViewDetector->getView());
    }

    public function testGetViewWhenRequestContainsViewAttribute()
    {
        $view = 'test';
        $request = Request::create('url');
        $request->attributes->set('view', $view);

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);

        self::assertSame($view, $this->docViewDetector->getView());
        // test that the view is cached
        self::assertSame($view, $this->docViewDetector->getView());
    }

    public function testSetView()
    {
        $view = 'test';

        $this->docViewDetector->setView($view);

        $this->requestStack->expects(self::never())
            ->method('getMasterRequest');

        self::assertEquals($view, $this->docViewDetector->getView());
    }

    public function testGetRequestTypeWhenNoProviderThatCanDetectRequestType()
    {
        $requestTypeProvider = $this->createMock(RequestTypeProviderInterface::class);
        $this->docViewDetector->addRequestTypeProvider($requestTypeProvider);

        $requestTypeProvider->expects(self::once())
            ->method('getRequestType')
            ->willReturn(null);

        $requestType = $this->docViewDetector->getRequestType();
        self::assertInstanceOf(RequestType::class, $requestType);
        self::assertTrue($requestType->isEmpty());

        // test that the request type is cached
        $requestType = $this->docViewDetector->getRequestType();
        self::assertInstanceOf(RequestType::class, $requestType);
        self::assertTrue($requestType->isEmpty());
    }

    public function testGetRequestTypeWhenProviderDetectsRequestType()
    {
        $requestTypeProvider = $this->createMock(RequestTypeProviderInterface::class);
        $this->docViewDetector->addRequestTypeProvider($requestTypeProvider);

        $requestTypeProvider->expects(self::once())
            ->method('getRequestType')
            ->willReturn(new RequestType(['test']));

        $requestType = $this->docViewDetector->getRequestType();
        self::assertInstanceOf(RequestType::class, $requestType);
        self::assertTrue($requestType->contains('test'));

        // test that the request type is cached
        $requestType = $this->docViewDetector->getRequestType();
        self::assertInstanceOf(RequestType::class, $requestType);
        self::assertTrue($requestType->contains('test'));
    }

    public function testShouldClearRequestTypeWhenViewChanged()
    {
        $requestTypeProvider = $this->createMock(RequestTypeProviderInterface::class);
        $this->docViewDetector->addRequestTypeProvider($requestTypeProvider);

        $requestTypeProvider->expects(self::exactly(2))
            ->method('getRequestType')
            ->willReturn(null);

        $this->docViewDetector->getRequestType();

        $this->docViewDetector->setView();
        $this->docViewDetector->getRequestType();
    }

    public function testGetVersionIfItWasNotSetExplicitly()
    {
        self::assertEquals('latest', $this->docViewDetector->getVersion());
    }

    public function testSetVersion()
    {
        $version = '1.2';

        $this->docViewDetector->setVersion($version);

        self::assertEquals($version, $this->docViewDetector->getVersion());
    }

    public function testShouldClearVersionWhenViewChanged()
    {
        $this->docViewDetector->setVersion('1.2');
        $this->docViewDetector->setView();

        self::assertEquals('latest', $this->docViewDetector->getVersion());
    }
}
