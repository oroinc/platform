<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\ContainerType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\FormType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class FormTypeTest extends BlockTypeTestCase
{
    /**
     * @dataProvider optionsDataProvider
     */
    public function testSetDefaultOptions($options, $expected)
    {
        $resolvedOptions = $this->resolveOptions(FormType::NAME, $options);
        $this->assertEquals($expected, $resolvedOptions);
    }

    public function optionsDataProvider()
    {
        return [
            'no options'  => [
                'options'  => [],
                'expected' => [
                    'form'      => null,
                    'form_name' => 'form',
                ],
            ],
            'all options' => [
                'options'  => [
                    'form'                  => null,
                    'form_name'             => 'test',
                    'form_action'           => 'test_action',
                    'form_route_name'       => 'test_route',
                    'form_route_parameters' => ['test_param' => true],
                    'form_method'           => 'POST',
                    'form_enctype'          => 'application/json',
                    'form_data'             => ['test'],
                    'form_prefix'           => 'test_prefix',
                    'form_field_prefix'     => 'test_field_prefix',
                    'form_group_prefix'     => 'test_group_prefix',
                    'render_rest'           => true,
                    'preferred_fields'      => 'first_name',
                    'groups'                => ['main', 'additional'],
                    'split_to_fields'       => true,
                ],
                'expected' => [
                    'form'                  => null,
                    'form_name'             => 'test',
                    'form_action'           => 'test_action',
                    'form_route_name'       => 'test_route',
                    'form_route_parameters' => ['test_param' => true],
                    'form_method'           => 'POST',
                    'form_enctype'          => 'application/json',
                    'form_data'             => ['test'],
                    'form_prefix'           => 'test_prefix',
                    'form_field_prefix'     => 'test_field_prefix',
                    'form_group_prefix'     => 'test_group_prefix',
                    'render_rest'           => true,
                    'preferred_fields'      => 'first_name',
                    'groups'                => ['main', 'additional'],
                    'split_to_fields'       => true,
                ],
            ],
        ];
    }

    public function testBuildBlock()
    {
        $formName = 'test_form';

        $builder = $this->getMock('Oro\Component\Layout\BlockBuilderInterface');
        $builder->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('form_id'));

        $layoutManipulator = $this->getMock('Oro\Component\Layout\LayoutManipulatorInterface');
        $builder->expects($this->exactly(3))
            ->method('getLayoutManipulator')
            ->willReturn($layoutManipulator);

        $layoutManipulator->expects($this->exactly(3))
            ->method('add')
            ->withConsecutive(
                [
                    'form_id_form_start',
                    'form_id',
                    'form_start',
                    [
                        'form'                  => null,
                        'form_name'             => 'test_form',
                        'form_route_name'       => 'test_route',
                        'form_route_parameters' => ['test_param' => true],
                        'form_method'           => 'POST',
                        'form_enctype'          => 'application/json',

                    ],
                ],
                [
                    'form_id_form_fields',
                    'form_id',
                    'form_fields',
                    [
                        'form'              => null,
                        'form_name'         => 'test_form',
                        'form_prefix'       => 'test_prefix',
                        'form_field_prefix' => 'test_field_prefix',
                        'form_group_prefix' => 'test_group_prefix',
                        'groups'            => ['main', 'additional'],
                        'split_to_fields'   => true,
                    ],
                ],
                [
                    'form_id_form_end',
                    'form_id',
                    'form_end',
                    [
                        'form'        => null,
                        'form_name'   => 'test_form',
                        'render_rest' => true,
                    ],
                ]

            );
        $type = new FormType();
        $options = $this->resolveOptions(
            $type,
            [
                'form_name'             => $formName,
                'form_route_name'       => 'test_route',
                'form_route_parameters' => ['test_param' => true],
                'form_method'           => 'POST',
                'form_enctype'          => 'application/json',
                'form_data'             => ['test'],
                'form_prefix'           => 'test_prefix',
                'form_field_prefix'     => 'test_field_prefix',
                'form_group_prefix'     => 'test_group_prefix',
                'render_rest'           => true,
                'preferred_fields'      => 'first_name',
                'groups'                => ['main', 'additional'],
                'split_to_fields'       => true,
            ]
        );

        $type->buildBlock($builder, $options);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(FormType::NAME);

        $this->assertSame(FormType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(FormType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
