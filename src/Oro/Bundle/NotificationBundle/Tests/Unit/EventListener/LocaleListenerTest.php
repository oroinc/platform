<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Oro\Bundle\NotificationBundle\EventListener\LocaleListener;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LocaleListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $transListener;

    public function setUp()
    {
        $this->transListener = $this->getMock('Gedmo\Translatable\TranslatableListener');
    }

    protected function tearDown()
    {
        unset($this->transListener);
    }

    /**
     * @param mixed $installed
     * @dataProvider onKernelRequestDataProvider
     */
    public function testOnKernelRequest($installed)
    {
        $request = new Request();
        $request->setLocale('en');

        if ($installed) {
            $this->transListener->expects($this->once())
                ->method('setDefaultLocale')
                ->with('en');
        }

        $this->listener = new LocaleListener($this->transListener, $installed);
        $this->listener->onKernelRequest($this->createGetResponseEvent($request));

        $events = LocaleListener::getSubscribedEvents();
        $this->assertTrue(!empty($events[KernelEvents::REQUEST]));
    }

    public function onKernelRequestDataProvider()
    {
        return [
            'application not installed with null' => [
                'installed' => null,
            ],
            'application installed with flag' => [
                'installed' => true,
            ],
        ];
    }

    /**
     * @param Request $request
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
