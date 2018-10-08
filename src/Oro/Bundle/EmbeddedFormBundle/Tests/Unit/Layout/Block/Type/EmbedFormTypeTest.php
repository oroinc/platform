<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormType;
use Oro\Bundle\EmbeddedFormBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;

class EmbedFormTypeTest extends BlockTypeTestCase
{
    public function testBuildBlock()
    {
        $formName = 'test_form';

        /** @var BlockBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock('Oro\Component\Layout\BlockBuilderInterface');
        $builder->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('form_id'));

        $layoutManipulator = $this->createMock('Oro\Component\Layout\LayoutManipulatorInterface');
        $builder->expects($this->exactly(3))
            ->method('getLayoutManipulator')
            ->willReturn($layoutManipulator);

        $layoutManipulator->expects($this->exactly(3))
            ->method('add')
            ->withConsecutive(
                [
                    'form_id_start',
                    'form_id',
                    'embed_form_start',
                    [
                        'form_name'             => 'test_form',
                        'form_route_name'       => 'test_route',
                        'form_route_parameters' => ['test_param' => true],
                        'form_method'           => 'POST',
                        'form_enctype'          => 'application/json',
                        'instance_name'         => '',
                        'additional_block_prefixes' => [],
                    ],
                ],
                [
                    'form_id_fields',
                    'form_id',
                    'embed_form_fields',
                    [
                        'form_name'         => 'test_form',
                        'form_prefix'       => 'test_prefix',
                        'form_field_prefix' => 'test_field_prefix',
                        'form_group_prefix' => 'test_group_prefix',
                        'groups'            => ['main', 'additional'],
                        'form_data'         => ['test'],
                        'preferred_fields'  => 'first_name',
                        'instance_name'         => '',
                        'additional_block_prefixes' => [],
                    ],
                ],
                [
                    'form_id_end',
                    'form_id',
                    'embed_form_end',
                    [
                        'form_name'   => 'test_form',
                        'render_rest' => true,
                        'instance_name' => '',
                        'additional_block_prefixes' => [],
                    ],
                ]
            );
        $type = new EmbedFormType();
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
                'additional_block_prefixes' => [],
            ]
        );

        $type->buildBlock($builder, new Options($options));
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(EmbedFormType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
