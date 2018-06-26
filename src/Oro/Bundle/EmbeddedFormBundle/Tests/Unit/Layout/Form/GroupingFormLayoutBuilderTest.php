<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Layout\Form;

use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormFieldType;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessor;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\GroupingFormLayoutBuilder;
use Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Form\Type\Stub\CompoundFormTypeStub;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormType;

class GroupingFormLayoutBuilderTest extends \PHPUnit\Framework\TestCase
{
    const ROOT_ID = 'rootId';
    const FORM_NAME = 'testForm';
    const FIELD_PREFIX = 'testForm_';
    const GROUP_PREFIX = 'testForm:group_';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $layoutManipulator;

    /** @var BlockBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $blockBuilder;

    /** @var GroupingFormLayoutBuilder */
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

        $this->builder = new GroupingFormLayoutBuilder();
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
        $form->add($this->getForm(false, TextType::class, 'field1'));
        $childForm = $this->getForm(true, CompoundFormTypeStub::class, 'field2');
        $childForm->add($this->getForm(false, TextareaType::class, 'field21'));
        $form->add($childForm);

        $this->layoutManipulator->expects($this->at(0))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1',
                self::GROUP_PREFIX . 'group2',
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(1))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2',
                self::GROUP_PREFIX . 'group2',
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(2))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2:field21',
                self::GROUP_PREFIX . 'group1',
                EmbedFormFieldType::NAME,
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

        $this->builder->build($formAccessor, $this->blockBuilder, new Options($options));
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
        $form->add($this->getForm(false, TextType::class, 'field1'));
        $childForm = $this->getForm(true, CompoundFormTypeStub::class, 'field2');
        $childForm->add($this->getForm(false, TextType::class, 'field21'));
        $childForm->add($this->getForm(false, TextareaType::class, 'field22'));
        $form->add($childForm);

        $this->layoutManipulator->expects($this->at(0))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2:field22',
                self::GROUP_PREFIX . 'group1',
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2.field22']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(1))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1',
                self::GROUP_PREFIX . 'group2',
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(2))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2',
                self::GROUP_PREFIX . 'group2',
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(3))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2:field21',
                self::GROUP_PREFIX . 'group1',
                EmbedFormFieldType::NAME,
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

        $this->builder->build($formAccessor, $this->blockBuilder, new Options($options));
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
        $form->add($this->getForm(false, TextType::class, 'field1'));
        $childForm = $this->getForm(true, CompoundFormTypeStub::class, 'field2');
        $childForm->add($this->getForm(false, TextareaType::class, 'field21'));
        $childForm->add($this->getForm(false, TextareaType::class, 'field22'));
        $form->add($childForm);

        $this->layoutManipulator->expects($this->at(0))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field1',
                self::GROUP_PREFIX . 'group2',
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(1))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2',
                self::GROUP_PREFIX . 'group1',
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(2))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2:field21',
                self::GROUP_PREFIX . 'group1',
                EmbedFormFieldType::NAME,
                ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2.field21']
            )
            ->will($this->returnSelf());
        $this->layoutManipulator->expects($this->at(3))
            ->method('add')
            ->with(
                self::FIELD_PREFIX . 'field2:field22',
                self::GROUP_PREFIX . 'group1',
                EmbedFormFieldType::NAME,
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

        $this->builder->build($formAccessor, $this->blockBuilder, new Options($options));
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
