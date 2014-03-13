<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model;

use Oro\Bundle\ReminderBundle\Model\UrlProvider;

class UrlProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UrlProvider
     */
    protected $urlProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->router = $this->getMockBuilder('Symfony\Component\Routing\RouterInterface')
            ->getMockForAbstractClass();

        $this->urlProvider = new UrlProvider($this->configManager, $this->router);
    }

    public function testGetUrlReturnEmptyStringIfMetadataNotExist()
    {
        $reminder = $this->getMock('Oro\Bundle\ReminderBundle\Entity\Reminder');
        $expected = '';
        $this->configManager->expects($this->once())->method('getEntityMetadata')->will($this->returnValue(null));
        $actual = $this->urlProvider->getUrl($reminder);
        $this->assertEquals($expected, $actual);
    }

    public function testGetUrlForView()
    {
        $reminder = $this->getMock('Oro\Bundle\ReminderBundle\Entity\Reminder');
        $expected = '/fake/path/for/view';
        $expectedId = 42;
        $metadata = new \StdClass();
        $metadata->routeView = $expected;
        $this->configManager->expects($this->once())->method('getEntityMetadata')->will($this->returnValue($metadata));
        $expectedParams = array('id' => $expectedId);
        $reminder->expects($this->once())->method('getRelatedEntityId')->will($this->returnValue($expectedId));
        $this->router->expects($this->once())->method('generate')->with($expected, $this->equalTo($expectedParams));
        $this->urlProvider->getUrl($reminder);
    }

    public function testGetUrlForIndex()
    {
        $reminder = $this->getMock('Oro\Bundle\ReminderBundle\Entity\Reminder');
        $expected = '/fake/path/for/view';
        $metadata = new \StdClass();
        $metadata->routeName = $expected;
        $this->configManager->expects($this->once())->method('getEntityMetadata')->will($this->returnValue($metadata));
        $this->router->expects($this->once())->method('generate')->with($expected);
        $this->urlProvider->getUrl($reminder);
    }
}
