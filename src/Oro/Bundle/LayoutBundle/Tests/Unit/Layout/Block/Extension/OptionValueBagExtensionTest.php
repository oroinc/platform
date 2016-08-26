<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Bundle\LayoutBundle\Layout\Block\Extension\OptionValueBagExtension;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\DataAccessorInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\OptionValueBag;

class OptionValueBagExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OptionValueBagExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new OptionValueBagExtension();
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(BaseType::NAME, $this->extension->getExtendedType());
    }

    /**
     * @param array $actual
     * @param array $expected
     * @dataProvider optionsDataProvider
     */
    public function testNormalizeOptions(array $actual, array $expected)
    {
        $context = new LayoutContext();

        /** @var DataAccessorInterface $dataAccessor */
        $dataAccessor = $this->getMock('Oro\Component\Layout\DataAccessorInterface');

        $options = new Options($actual);
        $this->extension->normalizeOptions($options, $context, $dataAccessor);
        $this->assertEquals($expected, $options->toArray());
    }

    /**
     * @param array $actual
     * @param array $expected
     * @param bool $isApplied
     * @dataProvider optionsDataProvider
     */
    public function testFinishView(array $actual, array $expected, $isApplied = true)
    {
        $context = new LayoutContext();
        $context['expressions_evaluate'] = $isApplied;

        $view = new BlockView();
        $view->vars = new Options($actual);

        /** @var BlockInterface|\PHPUnit_Framework_MockObject_MockObject $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $block->expects($this->any())
            ->method('getContext')
            ->willReturn($context);

        $this->extension->finishView(
            $view,
            $block,
            new Options(['resolve_value_bags' => $actual['resolve_value_bags']])
        );
        $this->assertEquals($expected, $view->vars->toArray());
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        return [
            'not applied' => [
                'actual' => [
                    'resolve_value_bags' => true,
                ],
                'expected' => [
                    'resolve_value_bags' => true,
                ],
                'isApplied' => false,
            ],
            'empty bag' => [
                'actual' => [
                    'resolve_value_bags' => true,
                    'option' => $this->createOptionValueBag([])
                ],
                'expected' => [
                    'resolve_value_bags' => true,
                    'option' => '',
                ]
            ],
            'string arguments' => [
                'actual' => [
                    'resolve_value_bags' => true,
                    'option' => $this->createOptionValueBag([
                        ['method' => 'add', 'arguments' => ['first']],
                        ['method' => 'add', 'arguments' => ['second']],
                        ['method' => 'replace', 'arguments' => ['first', 'result']],
                        ['method' => 'remove', 'arguments' => ['second']],
                    ])
                ],
                'expected' => [
                    'resolve_value_bags' => true,
                    'option' => 'result',
                ]
            ],
            'array arguments' => [
                'actual' => [
                    'resolve_value_bags' => true,
                    'option' => $this->createOptionValueBag([
                        ['method' => 'add', 'arguments' => [['one', 'two', 'three']]],
                        ['method' => 'remove', 'arguments' => [['one', 'three']]],
                    ])
                ],
                'expected' => [
                    'resolve_value_bags' => true,
                    'option' => ['two']
                ],
            ],
            'recursive processing' => [
                'actual' => [
                    'resolve_value_bags' => true,
                    'first_level_option_1' => $this->createOptionValueBag([
                        ['method' => 'add', 'arguments' => ['first_level']],
                    ]),
                    'first_level_option_2' => [
                        'second_level_option_1' => null,
                        'second_level_option_2' => $this->createOptionValueBag([
                            ['method' => 'add', 'arguments' => [['second_level']]],
                        ]),
                    ]
                ],
                'expected' => [
                    'resolve_value_bags' => true,
                    'first_level_option_1' => 'first_level',
                    'first_level_option_2' => [
                        'second_level_option_1' => null,
                        'second_level_option_2' => ['second_level']
                    ]
                ]
            ],
            'disabled' => [
                'actual' => [
                    'resolve_value_bags' => false,
                    'option' => $this->createOptionValueBag([
                        ['method' => 'add', 'arguments' => [['one', 'two', 'three']]],
                        ['method' => 'remove', 'arguments' => [['one', 'three']]],
                    ])
                ],
                'expected' => [
                    'resolve_value_bags' => false,
                    'option' => $this->createOptionValueBag([
                        ['method' => 'add', 'arguments' => [['one', 'two', 'three']]],
                        ['method' => 'remove', 'arguments' => [['one', 'three']]],
                    ])
                ],
            ],
        ];
    }

    /**
     * @param array $actions
     * @return OptionValueBag
     */
    protected function createOptionValueBag(array $actions)
    {
        $bag = new OptionValueBag();
        foreach ($actions as $action) {
            call_user_func_array([$bag, $action['method']], $action['arguments']);
        }

        return $bag;
    }
}
