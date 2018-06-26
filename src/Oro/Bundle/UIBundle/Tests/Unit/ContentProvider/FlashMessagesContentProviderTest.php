<?php
namespace Oro\Bundle\UIBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\UIBundle\ContentProvider\FlashMessagesContentProvider;

class FlashMessagesContentProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $session;

    /**
     * @var FlashMessagesContentProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider = new FlashMessagesContentProvider($this->session);
    }

    public function testGetContent()
    {
        $messages = array('test');
        $flashBag = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface')
            ->getMock();
        $flashBag->expects($this->once())
            ->method('all')
            ->will($this->returnValue($messages));
        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->will($this->returnValue($flashBag));
        $this->assertEquals($messages, $this->provider->getContent());
    }

    public function testGetName()
    {
        $this->assertEquals('flashMessages', $this->provider->getName());
    }
}
