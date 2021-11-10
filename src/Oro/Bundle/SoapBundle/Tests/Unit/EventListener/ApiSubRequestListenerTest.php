<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\EventListener;

use Oro\Bundle\SoapBundle\EventListener\ApiSubRequestListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApiSubRequestListenerTest extends \PHPUnit\Framework\TestCase
{
    private ApiSubRequestListener $listener;

    protected function setUp(): void
    {
        $this->listener = new ApiSubRequestListener();
        $this->listener->addRule(new RequestMatcher('^/api/rest'), ['stop' => false]);
        $this->listener->addRule(new RequestMatcher('^/another_api'));
        $this->listener->addRule(new RequestMatcher('^/'), ['stop' => true]);
    }

    public function testOnKernelRequestForMasterRequest(): void
    {
        $request = $this->createRequest();
        self::assertEquals('xml', $request->getRequestFormat(null));

        $this->listener->onKernelRequest($this->createEvent($request, HttpKernelInterface::MASTER_REQUEST));
        self::assertEquals('xml', $request->getRequestFormat(null));
    }

    public function testOnKernelRequestForSubRequest(): void
    {
        $request = $this->createRequest();
        self::assertEquals('xml', $request->getRequestFormat(null));

        $this->listener->onKernelRequest($this->createEvent($request));
        self::assertNull($request->getRequestFormat(null));
    }

    public function testOnKernelRequestForSubRequestWithoutFormat(): void
    {
        $request = $this->createRequest(null);
        self::assertNull($request->getRequestFormat(null));

        $this->listener->onKernelRequest($this->createEvent($request));
        self::assertNull($request->getRequestFormat(null));
    }

    public function testOnKernelRequestForNonRestApiSubRequest(): void
    {
        $request = $this->createRequest('xml', '/api/doc');
        self::assertEquals('xml', $request->getRequestFormat(null));

        $this->listener->onKernelRequest($this->createEvent($request));
        self::assertEquals('xml', $request->getRequestFormat(null));
    }

    public function testOnKernelRequestForExceptionHandlingSubRequest(): void
    {
        $request = $this->createRequest();
        $request->attributes->set('exception', new \Exception());
        self::assertEquals('xml', $request->getRequestFormat(null));

        $this->listener->onKernelRequest($this->createEvent($request));
        self::assertEquals('xml', $request->getRequestFormat(null));
    }

    private function createRequest(?string $format = 'xml', string $uri = '/api/rest/query'): Request
    {
        $request = Request::create($uri);
        if ($format) {
            $request->setRequestFormat($format);
        }

        return $request;
    }

    private function createEvent(Request $request, int $type = HttpKernelInterface::SUB_REQUEST): RequestEvent
    {
        $httpKernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($httpKernel, $request, $type);
    }
}
