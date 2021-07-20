<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\EventListener;

use Oro\Bundle\SoapBundle\EventListener\ApiSubRequestListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApiSubRequestListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ApiSubRequestListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new ApiSubRequestListener();
        $this->listener->addRule(new RequestMatcher('^/api/rest'), ['stop' => false]);
        $this->listener->addRule(new RequestMatcher('^/another_api'));
        $this->listener->addRule(new RequestMatcher('^/'), ['stop' => true]);
    }

    public function testOnKernelRequestForMasterRequest()
    {
        $request = $this->createRequest();
        $this->assertEquals('xml', $request->getRequestFormat(null));

        $this->listener->onKernelRequest($this->createEvent($request, HttpKernelInterface::MASTER_REQUEST));
        $this->assertEquals('xml', $request->getRequestFormat(null));
    }

    public function testOnKernelRequestForSubRequest()
    {
        $request = $this->createRequest();
        $this->assertEquals('xml', $request->getRequestFormat(null));

        $this->listener->onKernelRequest($this->createEvent($request));
        $this->assertNull($request->getRequestFormat(null));
    }

    public function testOnKernelRequestForSubRequestWithoutFormat()
    {
        $request = $this->createRequest(null);
        $this->assertNull($request->getRequestFormat(null));

        $this->listener->onKernelRequest($this->createEvent($request));
        $this->assertNull($request->getRequestFormat(null));
    }

    public function testOnKernelRequestForNonRestApiSubRequest()
    {
        $request = $this->createRequest('xml', '/api/doc');
        $this->assertEquals('xml', $request->getRequestFormat(null));

        $this->listener->onKernelRequest($this->createEvent($request));
        $this->assertEquals('xml', $request->getRequestFormat(null));
    }

    public function testOnKernelRequestForExceptionHandlingSubRequest()
    {
        $request = $this->createRequest();
        $request->attributes->set('exception', new \Exception());
        $this->assertEquals('xml', $request->getRequestFormat(null));

        $this->listener->onKernelRequest($this->createEvent($request));
        $this->assertEquals('xml', $request->getRequestFormat(null));
    }

    private function createRequest(?string $format = 'xml', string $uri = '/api/rest/query'): Request
    {
        $request = Request::create($uri);
        if ($format) {
            $request->setRequestFormat($format);
        }

        return $request;
    }

    private function createEvent(Request $request, int $type = HttpKernelInterface::SUB_REQUEST): GetResponseEvent
    {
        /** @var HttpKernelInterface $httpKernel */
        $httpKernel = $this->createMock(HttpKernelInterface::class);

        return new GetResponseEvent($httpKernel, $request, $type);
    }
}
