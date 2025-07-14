<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\ObjectIdAccessorInterface;
use Oro\Bundle\UIBundle\Provider\TabMenuWidgetProvider;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\TestBaseClass;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\TestClass;
use Oro\Bundle\UIBundle\Twig\TabExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TabMenuWidgetProviderTest extends TestCase
{
    private ObjectIdAccessorInterface&MockObject $objectIdAccessor;
    private TabExtension&MockObject $widgetProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->objectIdAccessor = $this->createMock(ObjectIdAccessorInterface::class);
        $this->widgetProvider = $this->createMock(TabExtension::class);
    }

    public function testSupportsWithoutEntityClass(): void
    {
        $provider = new TabMenuWidgetProvider(
            $this->objectIdAccessor,
            $this->widgetProvider,
            'test_menu'
        );

        $this->assertTrue($provider->supports(new TestClass()));
        $this->assertTrue($provider->supports(new \stdClass()));
    }

    public function testSupports(): void
    {
        $provider = new TabMenuWidgetProvider(
            $this->objectIdAccessor,
            $this->widgetProvider,
            'test_menu',
            TestBaseClass::class
        );

        $this->assertTrue($provider->supports(new TestBaseClass()));
        $this->assertTrue($provider->supports(new TestClass()));
        $this->assertFalse($provider->supports(new \stdClass()));
    }

    public function testGetWidgets(): void
    {
        $entity = new TestClass();
        $entityId = 123;

        $widgets = [
            ['name' => 'widget1'],
            ['name' => 'widget2'],
        ];

        $this->objectIdAccessor->expects($this->once())
            ->method('getIdentifier')
            ->with($this->identicalTo($entity))
            ->willReturn($entityId);
        $this->widgetProvider->expects($this->once())
            ->method('getTabs')
            ->with('test_menu', ['id' => $entityId])
            ->willReturn($widgets);

        $provider = new TabMenuWidgetProvider(
            $this->objectIdAccessor,
            $this->widgetProvider,
            'test_menu'
        );
        $this->assertEquals($widgets, $provider->getWidgets($entity));
    }
}
