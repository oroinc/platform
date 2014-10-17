<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\ActionButtonWidgetProvider;

class ActionButtonWidgetProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $placeholderProvider;

    protected function setUp()
    {
        $this->placeholderProvider = $this->getMockBuilder('Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider')
            ->disableOriginalConstructor()
            ->getMock();
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
            ->will(
                $this->returnValueMap(
                    [
                        ['button_widget', ['entity' => $entity], ['template' => 'button_template']],
                        ['link_widget', ['entity' => $entity], ['template' => 'link_template']],
                    ]
                )
            );

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
            ->will($this->returnValue(null));

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
            ->will(
                $this->returnValueMap(
                    [
                        ['button_widget', ['entity' => $entity], ['template' => 'button_template']],
                        ['link_widget', ['entity' => $entity], null],
                    ]
                )
            );

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
            ->will($this->returnValue(['template' => 'button_template']));

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
