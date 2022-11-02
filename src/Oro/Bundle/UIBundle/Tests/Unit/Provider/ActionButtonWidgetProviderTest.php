<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider;
use Oro\Bundle\UIBundle\Provider\ActionButtonWidgetProvider;

class ActionButtonWidgetProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PlaceholderProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $placeholderProvider;

    protected function setUp(): void
    {
        $this->placeholderProvider = $this->createMock(PlaceholderProvider::class);
    }

    public function testSupports()
    {
        $entity = new \stdClass();

        $provider = new ActionButtonWidgetProvider(
            $this->placeholderProvider,
            'button_widget',
            'link_widget'
        );

        $this->assertTrue($provider->supports($entity));
    }

    public function testGetWidgets()
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

    public function testGetWidgetsButtonPlaceholderIsNotApplicable()
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

    public function testGetWidgetsLinkPlaceholderIsNotApplicable()
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

    public function testGetWidgetsNoLink()
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
