<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Layout\Form;

use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormFieldType;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessor;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormLayoutBuilder;
use Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Form\Type\Stub\CompoundFormTypeStub;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormType;

class FormLayoutBuilderTest extends \PHPUnit\Framework\TestCase
{
    const ROOT_ID = 'rootId';
    const FORM_NAME = 'testForm';
    const FIELD_PREFIX = 'testForm_';
    const GROUP_PREFIX = 'testForm:group_';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $layoutManipulator;

    /** @var BlockBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $blockBuilder;

    /** @var FormLayoutBuilder */
    protected $builder;

    protected function setUp()
    {
        $this->layoutManipulator = $this->createMock('Oro\Component\Layout\LayoutManipulatorInterface');
        $this->blockBuilder      = $this->createMock('Oro\Component\Layout\BlockBuilderInterface');
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

        $this->builder->build($formAccessor, $this->blockBuilder, new Options($options));
        $this->assertSame([], $formAccessor->getProcessedFields());
    }

    public function testFlatForm()
    {
        $options      = $this->getOptions();
        $form         = $this->getForm();
        $formAccessor = new FormAccessor($form);
        $form->add($this->getForm(false, TextType::class, 'field1'));
        $form->add($this->getForm(false, TextareaType::class, 'field2'));

        $this->layoutManipulator->expects($this->at(0))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1',
                self::ROOT_ID,
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(1))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2',
                self::ROOT_ID,
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->exactly(2))
            ->method('add');

        $this->builder->build($formAccessor, $this->blockBuilder, new Options($options));
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
        $childForm    = $this->getForm(true, CompoundFormTypeStub::class, 'field1');
        $childForm->add($this->getForm(false, TextareaType::class, 'field11'));
        $form->add($childForm);
        $form->add($this->getForm(false, TextType::class, 'field2'));

        $this->layoutManipulator->expects($this->at(0))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1',
                self::ROOT_ID,
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(1))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1:field11',
                self::ROOT_ID,
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1.field11']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(2))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2',
                self::ROOT_ID,
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->exactly(3))
            ->method('add');

        $this->builder->build($formAccessor, $this->blockBuilder, new Options($options));
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
        $childForm    = $this->getForm(true, CompoundFormTypeStub::class, 'field1');
        $childForm->add($this->getForm(false, TextType::class, 'field11'));
        $form->add($childForm);
        $form->add($this->getForm(false, TextareaType::class, 'field2'));

        $this->layoutManipulator->expects($this->at(0))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1',
                self::ROOT_ID,
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(1))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2',
                self::ROOT_ID,
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->exactly(2))
            ->method('add');

        $this->builder->addSimpleFormTypes([CompoundFormTypeStub::class]);
        $this->builder->build($formAccessor, $this->blockBuilder, new Options($options));
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
        $form->add($this->getForm(false, TextType::class, 'field1'));
        $form->add($this->getForm(false, TextareaType::class, 'field2'));

        $this->layoutManipulator->expects($this->at(0))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2',
                self::ROOT_ID,
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(1))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1',
                self::ROOT_ID,
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->exactly(2))
            ->method('add');

        $this->builder->build($formAccessor, $this->blockBuilder, new Options($options));
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
        $form->add($this->getForm(false, TextType::class, 'field1'));
        $childForm = $this->getForm(true, CompoundFormTypeStub::class, 'field2');
        $childForm->add($this->getForm(false, TextType::class, 'field21'));
        $form->add($childForm);

        $this->layoutManipulator->expects($this->at(0))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2:field21',
                self::ROOT_ID,
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2.field21']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(1))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1',
                self::ROOT_ID,
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(2))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2',
                self::ROOT_ID,
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->exactly(3))
            ->method('add');

        $this->builder->build($formAccessor, $this->blockBuilder, new Options($options));
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
     * @param string $innerType
     * @param string $name
     *
     * @return FormInterface
     */
    protected function getForm($compound = true, $innerType = TextType::class, $name = 'some_form')
    {
        $formConfig = $this->createMock('Symfony\Component\Form\FormConfigInterface');
        $form       = new Form($formConfig);
        $resolvedType = new ResolvedFormType(new $innerType());
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
