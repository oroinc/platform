<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Provider;

use Oro\Bundle\ActivityBundle\Provider\MenuActivityWidgetProvider;
use Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Target;

class MenuActivityWidgetProviderTest extends \PHPUnit_Framework_TestCase
{
    const MENU_NAME           = 'test_menu';
    const TARGET_ENTITY_CLASS = 'Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity\Target';

    /** @var MenuActivityWidgetProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $widgetProvider;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->widgetProvider = $this->getMockBuilder('Oro\Bundle\UIBundle\Twig\TabExtension')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new MenuActivityWidgetProvider(
            $this->doctrineHelper,
            $this->widgetProvider,
            self::MENU_NAME,
            self::TARGET_ENTITY_CLASS
        );
    }

    public function testSupports()
    {
        $this->assertTrue($this->provider->supports(new Target()));
        $this->assertFalse($this->provider->supports(new \stdClass()));
    }

    public function testGetWidgets()
    {
        $entity   = new Target();
        $entityId = 123;

        $widgets = [
            ['name' => 'widget1'],
            ['name' => 'widget2'],
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue($entityId));
        $this->widgetProvider->expects($this->once())
            ->method('getTabs')
            ->with(self::MENU_NAME, ['id' => $entityId])
            ->will($this->returnValue($widgets));

        $this->assertEquals(
            $widgets,
            $this->provider->getWidgets($entity)
        );
    }
}
