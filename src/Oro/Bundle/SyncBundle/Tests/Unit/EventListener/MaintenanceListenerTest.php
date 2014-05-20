<?php
namespace Oro\Bundle\SyncBundle\Tests\Unit\EventListener;

use Oro\Bundle\SyncBundle\EventListener\MaintenanceListener;

class MaintenanceListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $topicPublisher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $securityFacade;

    protected function setUp()
    {
        $this->topicPublisher = $this->getMockBuilder('Oro\Bundle\SyncBundle\Wamp\TopicPublisher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->topicPublisher);
    }

    public function testOnModeOn()
    {
        $expectedUserId = 0;
        $this->topicPublisher
            ->expects($this->once())
            ->method('send')
            ->with('oro/maintenance', array('isOn' => true, 'userId' => $expectedUserId));
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUserId')
            ->will($this->returnValue($expectedUserId));
        /** @var MaintenanceListener $publisher */
        $publisher = new MaintenanceListener($this->topicPublisher, $this->securityFacade);
        $publisher->onModeOn();
    }

    public function testOnModeOff()
    {
        $expectedUserId = 42;
        $this->topicPublisher
            ->expects($this->once())
            ->method('send')
            ->with('oro/maintenance', array('isOn' => false, 'userId' => $expectedUserId));
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUserId')
            ->will($this->returnValue($expectedUserId));
        /** @var MaintenanceListener $publisher */
        $publisher = new MaintenanceListener($this->topicPublisher, $this->securityFacade);
        $publisher->onModeOff();
    }
}
