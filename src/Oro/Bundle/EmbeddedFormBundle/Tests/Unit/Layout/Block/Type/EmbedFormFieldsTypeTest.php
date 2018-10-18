<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormFieldsType;
use Oro\Bundle\EmbeddedFormBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\Form\FormView;

class EmbedFormFieldsTypeTest extends BlockTypeTestCase
{
    /**
     * @dataProvider optionsDataProvider
     *
     * @param array $options
     * @param array $expected
     */
    public function testConfigureOptions($options, $expected)
    {
        $resolvedOptions = $this->resolveOptions(EmbedFormFieldsType::NAME, $options);
        $this->assertEquals($expected, $resolvedOptions);
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        return [
            'no options'                     => [
                'options'  => [],
                'expected' => [
                    'form'              => null,
                    'form_name'         => 'form',
                    'preferred_fields'  => [],
                    'groups'            => [],
                    'form_prefix'       => 'form',
                    'form_field_prefix' => 'form_',
                    'form_group_prefix' => 'form:group_',
                    'instance_name'     => '',
                    'visible'           => true,
                ]
            ],
            'with form_name'                 => [
                'options'  => [
                    'form_name' => 'test'
                ],
                'expected' => [
                    'form'              => null,
                    'form_name'         => 'test',
                    'preferred_fields'  => [],
                    'groups'            => [],
                    'form_prefix'       => 'test',
                    'form_field_prefix' => 'test_',
                    'form_group_prefix' => 'test:group_',
                    'instance_name'     => '',
                    'visible'           => true,
                ]
            ],
            'with form_name and form_prefix' => [
                'options'  => [
                    'form_name'   => 'test_form',
                    'form_prefix' => 'test_prefix'
                ],
                'expected' => [
                    'form'              => null,
                    'form_name'         => 'test_form',
                    'preferred_fields'  => [],
                    'groups'            => [],
                    'form_prefix'       => 'test_prefix',
                    'form_field_prefix' => 'test_prefix_',
                    'form_group_prefix' => 'test_prefix:group_',
                    'instance_name'     => '',
                    'visible'           => true,
                ]
            ],
            'all options'                    => [
                'options'  => [
                    'form_name'         => 'test',
                    'preferred_fields'  => ['field1'],
                    'groups'            => ['group1' => ['title' => 'TestGroup']],
                    'form_prefix'       => 'form',
                    'form_field_prefix' => 'form_field_prefix_',
                    'form_group_prefix' => 'form_group_prefix_',
                ],
                'expected' => [
                    'form'              => null,
                    'form_name'         => 'test',
                    'preferred_fields'  => ['field1'],
                    'groups'            => ['group1' => ['title' => 'TestGroup']],
                    'form_prefix'       => 'form',
                    'form_field_prefix' => 'form_field_prefix_',
                    'form_group_prefix' => 'form_group_prefix_',
                    'instance_name'     => '',
                    'visible'           => true,
                ]
            ]
        ];
    }

    public function testBuildBlock()
    {
        $formName = 'test_form';

        $formAccessor = $this->createMock('Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessorInterface');

        $this->context->set($formName, $formAccessor);

        $builder = $this->createMock('Oro\Component\Layout\BlockBuilderInterface');
        $builder->expects($this->any())
            ->method('getContext')
            ->will($this->returnValue($this->context));

        $formLayoutBuilder = $this->createMock('Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormLayoutBuilderInterface');

        $type = new EmbedFormFieldsType($formLayoutBuilder);
        $options = new Options($this->resolveOptions(
            $type,
            [
                'form_name'       => $formName,
            ]
        ));

        $formLayoutBuilder->expects($this->once())
            ->method('build')
            ->with($this->identicalTo($formAccessor), $this->identicalTo($builder), $options);

        $type->buildBlock($builder, $options);

        $this->assertSame(
            $formAccessor,
            $this->context->get($formName)
        );
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Undefined index: test_form.
     */
    public function testBuildBlockWithoutForm()
    {
        $builder = $this->createMock('Oro\Component\Layout\BlockBuilderInterface');
        $builder->expects($this->any())
            ->method('getContext')
            ->will($this->returnValue($this->context));

        $type = $this->getBlockType(EmbedFormFieldsType::NAME);
        $options = $this->resolveOptions(
            $type,
            ['form_name' => 'test_form']
        );
        $type->buildBlock($builder, new Options($options));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "context[test_form]" argument type. Expected
     *                           "Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessorInterface", "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testBuildBlockWithInvalidForm()
    {
        $formName = 'test_form';

        $this->context->set($formName, 123);

        $builder = $this->createMock('Oro\Component\Layout\BlockBuilderInterface');
        $builder->expects($this->any())
            ->method('getContext')
            ->will($this->returnValue($this->context));

        $type = $this->getBlockType(EmbedFormFieldsType::NAME);
        $options = $this->resolveOptions(
            $type,
            ['form_name' => $formName]
        );
        $type->buildBlock($builder, new Options($options));
    }

    public function testBuildView()
    {
        $formLayoutBuilder = $this->createMock('Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormLayoutBuilderInterface');
        $type = new EmbedFormFieldsType($formLayoutBuilder);

        $view = new BlockView();
        $block = $this->createMock('Oro\Component\Layout\BlockInterface');

        $type->buildView(
            $view,
            $block,
            new Options(['form' => null, 'form_name' => 'form'])
        );
        $this->assertEquals(null, $view->vars['form']);
        $this->assertEquals('form', $view->vars['form_name']);
        $this->assertEquals(null, $view->vars['form_data']);
    }

    public function testFinishView()
    {
        $formLayoutBuilder = $this->createMock('Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormLayoutBuilderInterface');
        $type = new EmbedFormFieldsType($formLayoutBuilder);

        $formName = 'form';
        $rootView = new BlockView();
        $view = new BlockView($rootView);
        $block = $this->createMock('Oro\Component\Layout\BlockInterface');
        $formAccessor = $this->createMock('Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessorInterface');
        $context = new LayoutContext();
        $formView = new FormView();
        $formAccessor->expects($this->any())
            ->method('getView')
            ->will($this->returnValue($formView));
        $view->vars['form_name'] = $formName;

        $formView->children['field1'] = new FormView($formView);
        $formView->children['field2'] = new FormView($formView);
        $field3View = new FormView($formView);
        $formView->children['field3'] = $field3View;
        $field3View->children['field31'] = new FormView($field3View);
        $field3View->children['field32'] = new FormView($field3View);

        $view->children['block1'] = new BlockView($view);
        $view->children['block1']->vars['form'] = $formView->children['field1'];
        $rootView->children['block'] = $view;
        $rootView->children['block3'] = new BlockView($rootView);
        $rootView->children['block3']->vars['form'] = $field3View->children['field31'];
        $rootView->children['block4'] = new BlockView($rootView);
        // emulate remove form field blocks and then add new blocks with same ids
        $view->children['block2'] = new BlockView($view);
        $rootView->children['block4']->vars['form'] = new FormView();

        $this->setLayoutBlocks(['root' => $rootView]);

        $context->set('form', $formAccessor);

        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $formAccessor->expects($this->once())
            ->method('getProcessedFields')
            ->will(
                $this->returnValue(
                    [
                        'field1'         => 'block1',
                        'field2'         => 'block2',
                        'field3.field31' => 'block3',
                        'field3.field32' => 'block4'
                    ]
                )
            );

        $type->finishView($view, $block);

        $this->assertFalse($formView->isRendered());
        $this->assertFalse($formView['field1']->isRendered());
        $this->assertTrue($formView['field2']->isRendered());
        $this->assertFalse($formView['field3']['field31']->isRendered());
        $this->assertTrue($formView['field3']['field32']->isRendered());
    }

    public function testFinishViewWhenFormBlockIsRoot()
    {
        $formLayoutBuilder = $this->createMock('Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormLayoutBuilderInterface');
        $type = new EmbedFormFieldsType($formLayoutBuilder);

        $view = new BlockView();
        $block = $this->createMock('Oro\Component\Layout\BlockInterface');
        $formAccessor = $this->createMock('Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessorInterface');
        $context = new LayoutContext();
        $formView = new FormView();
        $formAccessor->expects($this->any())
            ->method('getView')
            ->will($this->returnValue($formView));
        $view->vars['form_name'] = 'form';

        $formView->children['field1'] = new FormView($formView);
        $formView->children['field2'] = new FormView($formView);
        $field3View = new FormView($formView);
        $formView->children['field3'] = $field3View;
        $field3View->children['field31'] = new FormView($field3View);
        $field3View->children['field32'] = new FormView($field3View);

        $view->children['block1'] = new BlockView($view);
        $view->children['block1']->vars['form'] = $formView['field1'];
        $view->children['block3'] = new BlockView($view);
        $view->children['block3']->vars['form'] = $field3View['field31'];

        $this->setLayoutBlocks(['root' => $view]);

        $context->set('form', $formAccessor);

        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $formAccessor->expects($this->once())
            ->method('getProcessedFields')
            ->will(
                $this->returnValue(
                    [
                        'field1'         => 'block1',
                        'field2'         => 'block2',
                        'field3.field31' => 'block3',
                        'field3.field32' => 'block4'
                    ]
                )
            );

        $type->finishView($view, $block);

        $this->assertFalse($formView->isRendered());
        $this->assertFalse($formView['field1']->isRendered());
        $this->assertTrue($formView['field2']->isRendered());
        $this->assertFalse($formView['field3']['field31']->isRendered());
        $this->assertTrue($formView['field3']['field32']->isRendered());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(EmbedFormFieldsType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
