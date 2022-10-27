<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\ActionButtonLabelProvider;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\TestBaseClass;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\TestClass;
use Symfony\Contracts\Translation\TranslatorInterface;

class ActionButtonLabelProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testProvider(?object $obj, string $expectedLabel, string $expectedWidgetTitle)
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $provider = new ActionButtonLabelProvider(
            $translator,
            [
                ''               => [
                    'label' => 'default label'
                ],
                'stdClass'       => [
                    'label' => 'stdClass label'
                ],
                TestClass::class => [
                    'label'        => 'TestClass label',
                    'widget_title' => 'TestClass widget title'
                ]
            ]
        );

        $translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnArgument(0);

        $this->assertEquals($expectedLabel, $provider->getLabel($obj));
        $this->assertEquals($expectedWidgetTitle, $provider->getWidgetTitle($obj));
    }

    public function dataProvider(): array
    {
        return [
            'null obj'               => [null, 'default label', 'default label'],
            'obj, no widget title'   => [new \stdClass(), 'stdClass label', 'stdClass label'],
            'obj, with widget title' => [new TestClass(), 'TestClass label', 'TestClass widget title'],
            'unknown obj'            => [new TestBaseClass(), 'default label', 'default label'],
        ];
    }
}
