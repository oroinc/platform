<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Layout\Form;

use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormFieldType;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessor;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormLayoutBuilder;
use Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Form\Type\Stub\CompoundFormTypeStub;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormType;

class FormLayoutBuilderTest extends \PHPUnit\Framework\TestCase
{
    private const ROOT_ID = 'rootId';
    private const FORM_NAME = 'testForm';
    private const FIELD_PREFIX = 'testForm_';
    private const GROUP_PREFIX = 'testForm:group_';

    /** @var LayoutManipulatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $layoutManipulator;

    /** @var BlockBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $blockBuilder;

    /** @var FormLayoutBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->layoutManipulator = $this->createMock(LayoutManipulatorInterface::class);
        $this->blockBuilder = $this->createMock(BlockBuilderInterface::class);
        $this->blockBuilder->expects($this->any())
            ->method('getId')
            ->willReturn(self::ROOT_ID);
        $this->blockBuilder->expects($this->any())
            ->method('getLayoutManipulator')
            ->willReturn($this->layoutManipulator);

        $this->builder = new FormLayoutBuilder();
    }

    public function testEmptyForm()
    {
        $options = $this->getOptions();
        $form = $this->getForm();
        $formAccessor = new FormAccessor($form);

        $this->layoutManipulator->expects($this->never())
            ->method('add');

        $this->builder->build($formAccessor, $this->blockBuilder, new Options($options));
        $this->assertSame([], $formAccessor->getProcessedFields());
    }

    public function testFlatForm()
    {
        $options = $this->getOptions();
        $form = $this->getForm();
        $formAccessor = new FormAccessor($form);
        $form->add($this->getForm(false, TextType::class, 'field1'));
        $form->add($this->getForm(false, TextareaType::class, 'field2'));

        $this->layoutManipulator->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [
                    self::FIELD_PREFIX . 'field1',
                    self::ROOT_ID,
                    EmbedFormFieldType::NAME,
                    ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
                ],
                [
                    self::FIELD_PREFIX . 'field2',
                    self::ROOT_ID,
                    EmbedFormFieldType::NAME,
                    ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
                ]
            )
            ->willReturnSelf();

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
        $options = $this->getOptions();
        $form = $this->getForm();
        $formAccessor = new FormAccessor($form);
        $childForm = $this->getForm(true, CompoundFormTypeStub::class, 'field1');
        $childForm->add($this->getForm(false, TextareaType::class, 'field11'));
        $form->add($childForm);
        $form->add($this->getForm(false, TextType::class, 'field2'));

        $this->layoutManipulator->expects($this->exactly(3))
            ->method('add')
            ->withConsecutive(
                [
                    self::FIELD_PREFIX . 'field1',
                    self::ROOT_ID,
                    EmbedFormFieldType::NAME,
                    ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
                ],
                [
                    self::FIELD_PREFIX . 'field1:field11',
                    self::ROOT_ID,
                    EmbedFormFieldType::NAME,
                    ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1.field11']
                ],
                [
                    self::FIELD_PREFIX . 'field2',
                    self::ROOT_ID,
                    EmbedFormFieldType::NAME,
                    ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
                ]
            )
            ->willReturnSelf();

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
        $options = $this->getOptions();
        $form = $this->getForm();
        $formAccessor = new FormAccessor($form);
        $childForm = $this->getForm(true, CompoundFormTypeStub::class, 'field1');
        $childForm->add($this->getForm(false, TextType::class, 'field11'));
        $form->add($childForm);
        $form->add($this->getForm(false, TextareaType::class, 'field2'));

        $this->layoutManipulator->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [
                    self::FIELD_PREFIX . 'field1',
                    self::ROOT_ID,
                    EmbedFormFieldType::NAME,
                    ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
                ],
                [
                    self::FIELD_PREFIX . 'field2',
                    self::ROOT_ID,
                    EmbedFormFieldType::NAME,
                    ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
                ]
            )
            ->willReturnSelf();

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
        $options = $this->getOptions();
        $options['preferred_fields'] = ['field2'];

        $form = $this->getForm();
        $formAccessor = new FormAccessor($form);
        $form->add($this->getForm(false, TextType::class, 'field1'));
        $form->add($this->getForm(false, TextareaType::class, 'field2'));

        $this->layoutManipulator->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [
                    self::FIELD_PREFIX . 'field2',
                    self::ROOT_ID,
                    EmbedFormFieldType::NAME,
                    ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
                ],
                [
                    self::FIELD_PREFIX . 'field1',
                    self::ROOT_ID,
                    EmbedFormFieldType::NAME,
                    ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
                ]
            )
            ->willReturnSelf();

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
        $options = $this->getOptions();
        $options['preferred_fields'] = ['field2.field21'];

        $form = $this->getForm();
        $formAccessor = new FormAccessor($form);
        $form->add($this->getForm(false, TextType::class, 'field1'));
        $childForm = $this->getForm(true, CompoundFormTypeStub::class, 'field2');
        $childForm->add($this->getForm(false, TextType::class, 'field21'));
        $form->add($childForm);

        $this->layoutManipulator->expects($this->exactly(3))
            ->method('add')
            ->withConsecutive(
                [
                    self::FIELD_PREFIX . 'field2:field21',
                    self::ROOT_ID,
                    EmbedFormFieldType::NAME,
                    ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2.field21']
                ],
                [
                    self::FIELD_PREFIX . 'field1',
                    self::ROOT_ID,
                    EmbedFormFieldType::NAME,
                    ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field1']
                ],
                [
                    self::FIELD_PREFIX . 'field2',
                    self::ROOT_ID,
                    EmbedFormFieldType::NAME,
                    ['form' => null, 'form_name' => self::FORM_NAME, 'field_path' => 'field2']
                ]
            )
            ->willReturnSelf();

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

    private function getForm(
        bool $compound = true,
        string $innerType = TextType::class,
        string $name = 'some_form'
    ): FormInterface {
        $formConfig = $this->createMock(FormConfigInterface::class);
        $resolvedType = new ResolvedFormType(new $innerType());
        $formConfig->expects($this->any())
            ->method('getCompound')
            ->willReturn($compound);
        $formConfig->expects($this->any())
            ->method('getDataMapper')
            ->willReturn($this->createMock(DataMapperInterface::class));
        $formConfig->expects($this->any())
            ->method('getType')
            ->willReturn($resolvedType);
        $formConfig->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return new Form($formConfig);
    }

    private function getOptions(): array
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
