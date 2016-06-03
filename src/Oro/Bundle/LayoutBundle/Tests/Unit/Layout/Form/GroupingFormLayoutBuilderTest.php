<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormType;

use Oro\Component\Layout\BlockBuilderInterface;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Block\Type\FormFieldType;
use Oro\Bundle\LayoutBundle\Layout\Form\GroupingFormLayoutBuilder;

class GroupingFormLayoutBuilderTest extends \PHPUnit_Framework_TestCase
{
    const ROOT_ID = 'rootId';
    const FORM_NAME = 'testForm';
    const FIELD_PREFIX = 'testForm_';
    const GROUP_PREFIX = 'testForm:group_';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $layoutManipulator;

    /** @var BlockBuilderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $blockBuilder;

    /** @var GroupingFormLayoutBuilder */
    protected $builder;

    protected function setUp()
    {
        $this->layoutManipulator = $this->getMock('Oro\Component\Layout\LayoutManipulatorInterface');
        $this->blockBuilder      = $this->getMock('Oro\Component\Layout\BlockBuilderInterface');
        $this->blockBuilder->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(self::ROOT_ID));
        $this->blockBuilder->expects($this->any())
            ->method('getLayoutManipulator')
            ->will($this->returnValue($this->layoutManipulator));

        $this->builder = new GroupingFormLayoutBuilder();
    }

    /**
     * @param bool   $compound
     * @param string $type
     * @param string $name
     *
     * @return FormInterface
     */
    protected function getForm($compound = true, $type = 'form', $name = 'some_form')
    {
        $formConfig = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $form       = new Form($formConfig);
        $formType   = $this->getMock('Symfony\Component\Form\FormTypeInterface');
        $formType->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($type));
        $resolvedType = new ResolvedFormType($formType);
        $formConfig->expects($this->any())
            ->method('getCompound')
            ->will($this->returnValue($compound));
        $formConfig->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($resolvedType));
        $formConfig->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $form;
    }

    public function testGrouping()
    {
        $options           = $this->getOptions();
        $options['groups'] = [
            'group1' => [
                'title'  => 'Group 1',
                'fields' => ['field2.field21']
            ],
            'group2' => [
                'title'   => 'Group 2',
                'default' => true
            ],
            'group3' => [
                'title' => 'Group 3'
            ]
        ];

        $form         = $this->getForm();
        $formAccessor = new FormAccessor($form);
        $form->add($this->getForm(false, 'type1', 'field1'));
        $childForm = $this->getForm(true, 'type2', 'field2');
        $childForm->add($this->getForm(false, 'type21', 'field21'));
        $form->add($childForm);

        $this->layoutManipulator->expects($this->at(0))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1',
                self::GROUP_PREFIX . 'group2',
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(1))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2',
                self::GROUP_PREFIX . 'group2',
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(2))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2:field21',
                self::GROUP_PREFIX . 'group1',
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2.field21']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(3))
            ->method('add')
            ->with(
                self::GROUP_PREFIX . 'group1',
                self::ROOT_ID,
                'fieldset',
                ['title' => 'Group 1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(4))
            ->method('add')
            ->with(
                self::GROUP_PREFIX . 'group2',
                self::ROOT_ID,
                'fieldset',
                ['title' => 'Group 2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->exactly(5))
            ->method('add');

        $this->builder->build($formAccessor, $this->blockBuilder, $options);
        $this->assertSame(
            [
                'field1'         => self::FIELD_PREFIX . 'field1',
                'field2'         => self::FIELD_PREFIX . 'field2',
                'field2.field21' => self::FIELD_PREFIX . 'field2:field21'
            ],
            $formAccessor->getProcessedFields()
        );
    }

    public function testGroupingWithPreferredFields()
    {
        $options                     = $this->getOptions();
        $options['preferred_fields'] = ['field2.field22'];
        $options['groups']           = [
            'group1' => [
                'title'  => 'Group 1',
                'fields' => ['field2.field21', 'field2.field22']
            ],
            'group2' => [
                'title'   => 'Group 2',
                'default' => true
            ],
            'group3' => [
                'title' => 'Group 3'
            ]
        ];

        $form         = $this->getForm();
        $formAccessor = new FormAccessor($form);
        $form->add($this->getForm(false, 'type1', 'field1'));
        $childForm = $this->getForm(true, 'type2', 'field2');
        $childForm->add($this->getForm(false, 'type21', 'field21'));
        $childForm->add($this->getForm(false, 'type22', 'field22'));
        $form->add($childForm);

        $this->layoutManipulator->expects($this->at(0))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2:field22',
                self::GROUP_PREFIX . 'group1',
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2.field22']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(1))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1',
                self::GROUP_PREFIX . 'group2',
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(2))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2',
                self::GROUP_PREFIX . 'group2',
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(3))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2:field21',
                self::GROUP_PREFIX . 'group1',
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2.field21']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(4))
            ->method('add')
            ->with(
                self::GROUP_PREFIX . 'group1',
                self::ROOT_ID,
                'fieldset',
                ['title' => 'Group 1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(5))
            ->method('add')
            ->with(
                self::GROUP_PREFIX . 'group2',
                self::ROOT_ID,
                'fieldset',
                ['title' => 'Group 2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->exactly(6))
            ->method('add');

        $this->builder->build($formAccessor, $this->blockBuilder, $options);
        $this->assertSame(
            [
                'field2.field22' => self::FIELD_PREFIX . 'field2:field22',
                'field1'         => self::FIELD_PREFIX . 'field1',
                'field2'         => self::FIELD_PREFIX . 'field2',
                'field2.field21' => self::FIELD_PREFIX . 'field2:field21'
            ],
            $formAccessor->getProcessedFields()
        );
    }

    public function testGroupingByParentFieldPath()
    {
        $options           = $this->getOptions();
        $options['groups'] = [
            'group1' => [
                'title'  => 'Group 1',
                'fields' => ['field2']
            ],
            'group2' => [
                'title'   => 'Group 2',
                'default' => true
            ],
            'group3' => [
                'title' => 'Group 3'
            ]
        ];

        $form         = $this->getForm();
        $formAccessor = new FormAccessor($form);
        $form->add($this->getForm(false, 'type1', 'field1'));
        $childForm = $this->getForm(true, 'type2', 'field2');
        $childForm->add($this->getForm(false, 'type21', 'field21'));
        $childForm->add($this->getForm(false, 'type22', 'field22'));
        $form->add($childForm);

        $this->layoutManipulator->expects($this->at(0))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1',
                self::GROUP_PREFIX . 'group2',
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(1))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2',
                self::GROUP_PREFIX . 'group1',
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(2))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2:field21',
                self::GROUP_PREFIX . 'group1',
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2.field21']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(3))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2:field22',
                self::GROUP_PREFIX . 'group1',
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2.field22']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(4))
            ->method('add')
            ->with(
                self::GROUP_PREFIX . 'group1',
                self::ROOT_ID,
                'fieldset',
                ['title' => 'Group 1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(5))
            ->method('add')
            ->with(
                self::GROUP_PREFIX . 'group2',
                self::ROOT_ID,
                'fieldset',
                ['title' => 'Group 2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->exactly(6))
            ->method('add');

        $this->builder->build($formAccessor, $this->blockBuilder, $options);
        $this->assertSame(
            [
                'field1'         => self::FIELD_PREFIX . 'field1',
                'field2'         => self::FIELD_PREFIX . 'field2',
                'field2.field21' => self::FIELD_PREFIX . 'field2:field21',
                'field2.field22' => self::FIELD_PREFIX . 'field2:field22'
            ],
            $formAccessor->getProcessedFields()
        );
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        return [
            'form'              => null,
            'form_name'         => self::FORM_NAME,
            'preferred_fields'  => [],
            'groups'            => [],
            'form_field_prefix' => self::FIELD_PREFIX,
            'form_group_prefix' => self::GROUP_PREFIX
        ];
    }
}
