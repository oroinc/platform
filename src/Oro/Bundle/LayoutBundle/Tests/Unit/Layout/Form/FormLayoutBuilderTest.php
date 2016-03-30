<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormType;

use Oro\Component\Layout\BlockBuilderInterface;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\FormFieldType;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormLayoutBuilder;

class FormLayoutBuilderTest extends \PHPUnit_Framework_TestCase
{
    const ROOT_ID = 'rootId';
    const FORM_NAME = 'testForm';
    const FIELD_PREFIX = 'testForm_';
    const GROUP_PREFIX = 'testForm:group_';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $layoutManipulator;

    /** @var BlockBuilderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $blockBuilder;

    /** @var FormLayoutBuilder */
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

        $this->builder = new FormLayoutBuilder();
    }

    public function testEmptyForm()
    {
        $options      = $this->getOptions();
        $form         = $this->getForm();
        $formAccessor = new FormAccessor($form);

        $this->layoutManipulator->expects($this->never())
            ->method('add');

        $this->builder->build($formAccessor, $this->blockBuilder, $options);
        $this->assertSame([], $formAccessor->getProcessedFields());
    }

    public function testFlatForm()
    {
        $options      = $this->getOptions();
        $form         = $this->getForm();
        $formAccessor = new FormAccessor($form);
        $form->add($this->getForm(false, 'type1', 'field1'));
        $form->add($this->getForm(false, 'type2', 'field2'));

        $this->layoutManipulator->expects($this->at(0))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1',
                self::ROOT_ID,
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(1))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2',
                self::ROOT_ID,
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->exactly(2))
            ->method('add');

        $this->builder->build($formAccessor, $this->blockBuilder, $options);
        $this->assertSame(
            [
                'field1' => self::FIELD_PREFIX . 'field1',
                'field2' => self::FIELD_PREFIX . 'field2'
            ],
            $formAccessor->getProcessedFields()
        );
    }

    public function testCompoundChildForm()
    {
        $options      = $this->getOptions();
        $form         = $this->getForm();
        $formAccessor = new FormAccessor($form);
        $childForm    = $this->getForm(true, 'type1', 'field1');
        $childForm->add($this->getForm(false, 'type11', 'field11'));
        $form->add($childForm);
        $form->add($this->getForm(false, 'type2', 'field2'));

        $this->layoutManipulator->expects($this->at(0))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1',
                self::ROOT_ID,
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(1))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1:field11',
                self::ROOT_ID,
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1.field11']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(2))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2',
                self::ROOT_ID,
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->exactly(3))
            ->method('add');

        $this->builder->build($formAccessor, $this->blockBuilder, $options);
        $this->assertSame(
            [
                'field1'         => self::FIELD_PREFIX . 'field1',
                'field1.field11' => self::FIELD_PREFIX . 'field1:field11',
                'field2'         => self::FIELD_PREFIX . 'field2'
            ],
            $formAccessor->getProcessedFields()
        );
    }

    public function testCompoundChildFormWhichMarkedAsSimpleForm()
    {
        $options      = $this->getOptions();
        $form         = $this->getForm();
        $formAccessor = new FormAccessor($form);
        $childForm    = $this->getForm(true, 'type1', 'field1');
        $childForm->add($this->getForm(false, 'type11', 'field11'));
        $form->add($childForm);
        $form->add($this->getForm(false, 'type2', 'field2'));

        $this->layoutManipulator->expects($this->at(0))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1',
                self::ROOT_ID,
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(1))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2',
                self::ROOT_ID,
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->exactly(2))
            ->method('add');

        $this->builder->addSimpleFormTypes(['type1']);
        $this->builder->build($formAccessor, $this->blockBuilder, $options);
        $this->assertSame(
            [
                'field1' => self::FIELD_PREFIX . 'field1',
                'field2' => self::FIELD_PREFIX . 'field2'
            ],
            $formAccessor->getProcessedFields()
        );
    }

    public function testPreferredFields()
    {
        $options                     = $this->getOptions();
        $options['preferred_fields'] = ['field2'];

        $form         = $this->getForm();
        $formAccessor = new FormAccessor($form);
        $form->add($this->getForm(false, 'type1', 'field1'));
        $form->add($this->getForm(false, 'type2', 'field2'));

        $this->layoutManipulator->expects($this->at(0))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2',
                self::ROOT_ID,
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(1))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1',
                self::ROOT_ID,
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->exactly(2))
            ->method('add');

        $this->builder->build($formAccessor, $this->blockBuilder, $options);
        $this->assertSame(
            [
                'field2' => self::FIELD_PREFIX . 'field2',
                'field1' => self::FIELD_PREFIX . 'field1'
            ],
            $formAccessor->getProcessedFields()
        );
    }

    public function testPreferredCompoundFields()
    {
        $options                     = $this->getOptions();
        $options['preferred_fields'] = ['field2.field21'];

        $form         = $this->getForm();
        $formAccessor = new FormAccessor($form);
        $form->add($this->getForm(false, 'type1', 'field1'));
        $childForm = $this->getForm(true, 'type2', 'field2');
        $childForm->add($this->getForm(false, 'type21', 'field21'));
        $form->add($childForm);

        $this->layoutManipulator->expects($this->at(0))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2:field21',
                self::ROOT_ID,
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2.field21']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(1))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1',
                self::ROOT_ID,
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(2))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2',
                self::ROOT_ID,
                FormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->exactly(3))
            ->method('add');

        $this->builder->build($formAccessor, $this->blockBuilder, $options);
        $this->assertSame(
            [
                'field2.field21' => self::FIELD_PREFIX . 'field2:field21',
                'field1'         => self::FIELD_PREFIX . 'field1',
                'field2'         => self::FIELD_PREFIX . 'field2'
            ],
            $formAccessor->getProcessedFields()
        );
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

    /**
     * @return array
     */
    protected function getOptions()
    {
        return [
            'form'              => null,
            'form_name'         => self::FORM_NAME,
            'preferred_fields'  => [],
            'form_field_prefix' => self::FIELD_PREFIX,
            'form_group_prefix' => self::GROUP_PREFIX
        ];
    }
}
