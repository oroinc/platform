<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider;
use Oro\Bundle\UIBundle\Provider\ActionButtonWidgetProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActionButtonWidgetProviderTest extends TestCase
{
    private PlaceholderProvider&MockObject $placeholderProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->placeholderProvider = $this->createMock(PlaceholderProvider::class);
    }

    public function testSupports(): void
    {
        $entity = new \stdClass();

        $provider = new ActionButtonWidgetProvider(
            $this->placeholderProvider,
            'button_widget',
            'link_widget'
        );

        $this->assertTrue($provider->supports($entity));
    }

    public function testGetWidgets(): void
    {
        $entity = new \stdClass();

        $provider = new ActionButtonWidgetProvider(
            $this->placeholderProvider,
            'button_widget',
            'link_widget'
        );

        $this->placeholderProvider->expects($this->exactly(2))
            ->method('getItem')
            ->willReturnMap([
                ['button_widget', ['entity' => $entity], ['template' => 'button_template']],
                ['link_widget', ['entity' => $entity], ['template' => 'link_template']],
            ]);

        $this->assertEquals(
            [
                [
                    'name'   => 'button_widget',
                    'button' => [
                        'template' => 'button_template'
                    ],
                    'link'   => [
                        'template' => 'link_template'
                    ],
                ],
            ],
            $provider->getWidgets($entity)
        );
    }

    public function testGetWidgetsButtonPlaceholderIsNotApplicable(): void
    {
        $entity = new \stdClass();

        $provider = new ActionButtonWidgetProvider(
            $this->placeholderProvider,
            'button_widget',
            'link_widget'
        );

        $this->placeholderProvider->expects($this->once())
            ->method('getItem')
            ->with('button_widget', ['entity' => $entity])
            ->willReturn(null);

        $this->assertEquals(
            [],
            $provider->getWidgets($entity)
        );
    }

    public function testGetWidgetsLinkPlaceholderIsNotApplicable(): void
    {
        $entity = new \stdClass();

        $provider = new ActionButtonWidgetProvider(
            $this->placeholderProvider,
            'button_widget',
            'link_widget'
        );

        $this->placeholderProvider->expects($this->exactly(2))
            ->method('getItem')
            ->willReturnMap([
                ['button_widget', ['entity' => $entity], ['template' => 'button_template']],
                ['link_widget', ['entity' => $entity], null],
            ]);

        $this->assertEquals(
            [
                [
                    'name'   => 'button_widget',
                    'button' => [
                        'template' => 'button_template'
                    ],
                ],
            ],
            $provider->getWidgets($entity)
        );
    }

    public function testGetWidgetsNoLink(): void
    {
        $entity = new \stdClass();

        $provider = new ActionButtonWidgetProvider(
            $this->placeholderProvider,
            'button_widget',
            null
        );

        $this->placeholderProvider->expects($this->once())
            ->method('getItem')
            ->with('button_widget', ['entity' => $entity])
            ->willReturn(['template' => 'button_template']);

        $this->assertEquals(
            [
                [
                    'name'   => 'button_widget',
                    'button' => [
                        'template' => 'button_template'
                    ],
                ],
            ],
            $provider->getWidgets($entity)
        );
    }
}
