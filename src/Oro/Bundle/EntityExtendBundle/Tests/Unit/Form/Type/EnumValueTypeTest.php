<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\DefaultTranslator;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Validator;

use Oro\Bundle\EntityExtendBundle\Form\Type\EnumValueType;

class EnumValueTypeTest extends TypeTestCase
{
    /** @var EnumValueType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new EnumValueType();
    }

    protected function getExtensions()
    {
        $validator = new Validator(
            new ClassMetadataFactory(new LoaderChain([])),
            new ConstraintValidatorFactory(),
            new DefaultTranslator()
        );

        return [
            new PreloadedExtension(
                [],
                [
                    'form' => [
                        new FormTypeValidatorExtension($validator)
                    ]
                ]
            )
        ];
    }

    public function testSubmitValidDataForNewEnumValue()
    {
        $formData = [
            'label'      => 'Value 1',
            'is_default' => true,
            'priority'   => 1
        ];

        $form = $this->factory->create($this->type);
        $this->type->postSetData($this->getFormEvent($formData, $form));
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(
            [
                'id'         => null,
                'label'      => 'Value 1',
                'is_default' => true,
                'priority'   => '1'
            ],
            $form->getData()
        );

        $nameConstraints = $form->get('label')->getConfig()->getOption('constraints');
        $this->assertCount(3, $nameConstraints);

        $this->assertInstanceOf(
            'Symfony\Component\Validator\Constraints\NotBlank',
            $nameConstraints[0]
        );

        $this->assertInstanceOf(
            'Symfony\Component\Validator\Constraints\Length',
            $nameConstraints[1]
        );
        $this->assertEquals(255, $nameConstraints[1]->max);

        $this->assertInstanceOf(
            'Symfony\Component\Validator\Constraints\Callback',
            $nameConstraints[2]
        );
        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->once())->method('addViolation')->with(EnumValueType::INVALID_NAME_MESSAGE);
        call_user_func($nameConstraints[2]->methods[0], '!@#$', $context);
    }

    public function testSubmitValidDataForExistingEnumValue()
    {
        $formData = [
            'id'         => 'val1',
            'label'      => 'Value 1',
            'is_default' => true,
            'priority'   => 1
        ];

        $form = $this->factory->create($this->type);
        $this->type->postSetData($this->getFormEvent($formData, $form));
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(
            [
                'id'         => 'val1',
                'label'      => 'Value 1',
                'is_default' => true,
                'priority'   => '1'
            ],
            $form->getData()
        );

        $nameConstraints = $form->get('label')->getConfig()->getOption('constraints');
        $this->assertCount(2, $nameConstraints);
    }

    public function testGetName()
    {
        $this->assertEquals(
            'oro_entity_extend_enum_value',
            $this->type->getName()
        );
    }

    /**
     * @param array                                    $data
     * @param \PHPUnit_Framework_MockObject_MockObject $form
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFormEvent($data, $form = null)
    {
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($form));
        $event->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));

        return $event;
    }
}
