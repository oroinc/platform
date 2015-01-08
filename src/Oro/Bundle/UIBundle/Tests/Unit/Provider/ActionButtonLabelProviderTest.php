<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\ActionButtonLabelProvider;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\TestBaseClass;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\TestClass;

class ActionButtonLabelProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testProvider($obj, $expectedLabel, $expectedWidgetTitle)
    {
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $provider   = new ActionButtonLabelProvider(
            $translator,
            [
                ''                                                 => [
                    'label' => 'default label'
                ],
                'stdClass'                                         => [
                    'label' => 'stdClass label'
                ],
                'Oro\Bundle\UIBundle\Tests\Unit\Fixture\TestClass' => [
                    'label'        => 'TestClass label',
                    'widget_title' => 'TestClass widget title'
                ]
            ]
        );

        $translator->expects($this->exactly(2))
            ->method('trans')
            ->will($this->returnArgument(0));

        $this->assertEquals($expectedLabel, $provider->getLabel($obj));
        $this->assertEquals($expectedWidgetTitle, $provider->getWidgetTitle($obj));
    }

    public function dataProvider()
    {
        return [
            'null obj'               => [null, 'default label', 'default label'],
            'obj, no widget title'   => [new \stdClass(), 'stdClass label', 'stdClass label'],
            'obj, with widget title' => [new TestClass(), 'TestClass label', 'TestClass widget title'],
            'unknown obj'            => [new TestBaseClass(), 'default label', 'default label'],
        ];
    }
}
