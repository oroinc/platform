<?php

namespace Oro\Bundle\SoapBundle\Tests\EventListener;

use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\SoapBundle\EventListener\LocaleListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class LocaleListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocaleListener */
    protected $listener;

    /** @var string */
    protected $defaultLocale;

    protected function setUp()
    {
        $this->defaultLocale = \Locale::getDefault();
    }

    protected function tearDown()
    {
        \Locale::setDefault($this->defaultLocale);
    }

    public function testOnKernelRequest()
    {
        $customLocale = 'fr';

        $request = new Request(['locale' => $customLocale]);
        $request->server->set('REQUEST_URI', '/api/rest/test');
        $request->setDefaultLocale($this->defaultLocale);

        $translationListener = new TranslatableListener();
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();
        $container->expects($this->any())
            ->method('get')
            ->willReturn($translationListener);
        $this->listener = new LocaleListener($container);
        $this->listener->onKernelRequest($this->createGetResponseEvent($request));

        $this->assertEquals($customLocale, $request->getLocale());
        $this->assertEquals($customLocale, $translationListener->getListenerLocale());
    }

    /**
     * @param Request $request
     *
     * @return GetResponseEvent
     */
    protected function createGetResponseEvent(Request $request)
    {
        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest'))
            ->getMock();

        $event->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        return $event;
    }
}
