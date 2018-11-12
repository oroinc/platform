<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Event;

use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ResponseHashnavListenerTest extends \PHPUnit\Framework\TestCase
{
    const TEST_URL = 'http://test_url/';
    const TEMPLATE = 'OroNavigationBundle:HashNav:redirect.html.twig';

    /**
     * @var \Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener
     */
    protected $listener;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var \Symfony\Component\HttpFoundation\Response
     */
    protected $response;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $templating;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $event;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $tokenStorage;

    protected function setUp()
    {
        $this->response = new Response();
        $this->request  = Request::create(self::TEST_URL);
        $this->request->headers->add(array(ResponseHashnavListener::HASH_NAVIGATION_HEADER => true));
        $this->event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\FilterResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->request));

        $this->event->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->response));

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->templating = $this->createMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        $this->kernel = $this->createMock('Symfony\Component\HttpKernel\KernelInterface');
        $this->listener = $this->getListener(false);
    }

    public function testPlainRequest()
    {
        $testBody = 'test';
        $this->response->setContent($testBody);

        $this->listener->onResponse($this->event);

        $this->assertEquals($testBody, $this->response->getContent());
    }

    public function testHashRequestWOUser()
    {
        $this->response->setStatusCode(302);
        $this->response->headers->add(array('location' => self::TEST_URL));

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(false));

        $this->event->expects($this->once())
            ->method('setResponse');

        $this->templating->expects($this->once())
            ->method('renderResponse')
            ->with(
                self::TEMPLATE,
                array(
                    'full_redirect' => true,
                    'location'      => self::TEST_URL
                )
            )
            ->will($this->returnValue(new Response()));

        $this->listener->onResponse($this->event);
    }

    public function testHashRequestWithFullRedirectAttribute()
    {
        $this->response->setStatusCode(302);
        $this->response->headers->add(['location' => self::TEST_URL]);

        $this->request->attributes->set('_fullRedirect', true);

        $this->tokenStorage->expects($this->never())
            ->method('getToken');

        $this->event->expects($this->once())
            ->method('setResponse');

        $this->templating->expects($this->once())
            ->method('renderResponse')
            ->with(
                self::TEMPLATE,
                [
                    'full_redirect' => true,
                    'location'      => self::TEST_URL
                ]
            )
            ->will($this->returnValue(new Response()));

        $this->listener->onResponse($this->event);
    }

    public function testHashRequestNotFound()
    {
        $this->response->setStatusCode(404);
        $this->serverErrorHandle();
    }

    public function testFullRedirectProducedInProdEnv()
    {
        $expected = array('full_redirect' => 1, 'location' => self::TEST_URL);
        $this->response->headers->add(array('location' => self::TEST_URL));
        $response = new Response();
        $this->response->setStatusCode(503);
        $this->templating
            ->expects($this->once())
            ->method('renderResponse')
            ->with('OroNavigationBundle:HashNav:redirect.html.twig', $expected)
            ->will($this->returnValue($response));

        $this->event->expects($this->once())->method('setResponse')->with($response);
        $this->listener->onResponse($this->event);
    }

    public function testFullRedirectNotProducedInDevEnv()
    {
        $listener = $this->getListener(true);
        $this->response->headers->add(array('location' => self::TEST_URL));
        $this->response->setStatusCode(503);
        $this->templating->expects($this->never())->method('renderResponse');

        $this->event->expects($this->once())->method('setResponse');
        $listener->onResponse($this->event);
    }

    /**
     * @param bool $isDebug
     * @return ResponseHashnavListener
     */
    protected function getListener($isDebug)
    {
        return new ResponseHashnavListener($this->tokenStorage, $this->templating, $isDebug);
    }

    private function serverErrorHandle()
    {
        $this->event->expects($this->once())
            ->method('setResponse');

        $this->templating->expects($this->once())
            ->method('renderResponse')
            ->with(
                self::TEMPLATE,
                array(
                    'full_redirect' => true,
                    'location'      => self::TEST_URL
                )
            )
            ->will($this->returnValue(new Response()));

        $this->listener->onResponse($this->event);
    }
}
