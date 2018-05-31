<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Annotations\AnnotationReader;
use Oro\Bundle\EntityExtendBundle\Form\Type\UniqueKeyType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\Validator\Validation;

class UniqueKeyTypeTest extends TypeTestCase
{
    /**
     * @var array
     */
    private $fields = [
        'First Name' => 'firstName',
        'Last Name' => 'lastName',
        'Email' => 'email',
    ];

    protected function setUp()
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping(new AnnotationReader())
            ->getValidator();

        $this->factory =
            Forms::createFormFactoryBuilder()
                ->addExtensions([new ValidatorExtension($validator)])
                ->getFormFactory();

        $this->dispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->builder = new FormBuilder(null, null, $this->dispatcher, $this->factory);
    }

    public function testRequiredKeyChoiceOption()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "key_choices" is missing.');
        $this->factory->create(UniqueKeyType::class);
    }

    public function testType()
    {
        $formData = array(
            'name' => 'test',
            'key'  => array('firstName', 'lastName', 'email')
        );

        $form = $this->factory->create(UniqueKeyType::class, null, ['key_choices' => $this->fields]);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
    }

    public function testSubmitNotValidData()
    {
        $form = $this->factory->create(UniqueKeyType::class, null, ['key_choices' => $this->fields]);

        $formData = array(
            'name' => '',
            'key'  => []
        );

        $form->submit($formData);
        $this->assertFalse($form->isValid());
    }

    public function testNames()
    {
        $type = new UniqueKeyType();
        $this->assertEquals('oro_entity_extend_unique_key_type', $type->getName());
    }
}
