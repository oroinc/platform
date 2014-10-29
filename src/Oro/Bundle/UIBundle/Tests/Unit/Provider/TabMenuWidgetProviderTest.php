<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\TabMenuWidgetProvider;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\TestBaseClass;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\TestClass;

class TabMenuWidgetProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var TabMenuWidgetProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $objectIdAccessor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $widgetProvider;

    protected function setUp()
    {
        $this->objectIdAccessor = $this->getMock('Oro\Bundle\UIBundle\Provider\ObjectIdAccessorInterface');
        $this->widgetProvider   = $this->getMockBuilder('Oro\Bundle\UIBundle\Twig\TabExtension')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSupportsWithoutEntityClass()
    {
        $this->provider = new TabMenuWidgetProvider(
            $this->objectIdAccessor,
            $this->widgetProvider,
            'test_menu'
        );

        $this->assertTrue($this->provider->supports(new TestClass()));
        $this->assertTrue($this->provider->supports(new \stdClass()));
    }

    public function testSupports()
    {
        $this->provider = new TabMenuWidgetProvider(
            $this->objectIdAccessor,
            $this->widgetProvider,
            'test_menu',
            'Oro\Bundle\UIBundle\Tests\Unit\Fixture\TestBaseClass'
        );

        $this->assertTrue($this->provider->supports(new TestBaseClass()));
        $this->assertTrue($this->provider->supports(new TestClass()));
        $this->assertFalse($this->provider->supports(new \stdClass()));
    }

    public function testGetWidgets()
    {
        $this->provider = new TabMenuWidgetProvider(
            $this->objectIdAccessor,
            $this->widgetProvider,
            'test_menu'
        );

        $entity   = new TestClass();
        $entityId = 123;

        $widgets = [
            ['name' => 'widget1'],
            ['name' => 'widget2'],
        ];

        $this->objectIdAccessor->expects($this->once())
            ->method('getIdentifier')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue($entityId));
        $this->widgetProvider->expects($this->once())
            ->method('getTabs')
            ->with('test_menu', ['id' => $entityId])
            ->will($this->returnValue($widgets));

        $this->assertEquals(
            $widgets,
            $this->provider->getWidgets($entity)
        );
    }
}
