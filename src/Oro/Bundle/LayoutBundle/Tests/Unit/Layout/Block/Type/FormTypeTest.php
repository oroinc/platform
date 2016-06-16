<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\BaseType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\FormType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class FormTypeTest extends BlockTypeTestCase
{

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
                    ['form_name' => 'test_form'],
                ],
                [
                    'form_id_form_fields',
                    'form_id',
                    'form_fields',
                    ['form_name' => 'test_form'],
                ],
                [
                    'form_id_form_end',
                    'form_id',
                    'form_end',
                    ['form_name' => 'test_form']
                ]

            );
        $type = new FormType();
        $options = $this->resolveOptions(
            $type,
            [
                'form_name' => $formName
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

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
