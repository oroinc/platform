<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Annotations\AnnotationReader;
use Oro\Bundle\EntityExtendBundle\Form\Type\UniqueKeyType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\Validator\Validation;

class UniqueKeyTypeTest extends TypeTestCase
{
    private array $fields = [
        'First Name' => 'firstName',
        'Last Name' => 'lastName',
        'Email' => 'email',
    ];

    protected function setUp(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping(true)
            ->setDoctrineAnnotationReader(new AnnotationReader())
            ->getValidator();

        $this->factory =
            Forms::createFormFactoryBuilder()
                ->addExtensions([new ValidatorExtension($validator)])
                ->getFormFactory();

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->builder = new FormBuilder(null, null, $this->dispatcher, $this->factory);
    }

    public function testRequiredKeyChoiceOption(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "key_choices" is missing.');
        $this->factory->create(UniqueKeyType::class);
    }

    public function testType(): void
    {
        $formData = [
            'name' => 'test',
            'key'  => ['firstName', 'lastName', 'email'],
        ];

        $form = $this->factory->create(UniqueKeyType::class, null, ['key_choices' => $this->fields]);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }

    public function testSubmitNotValidData(): void
    {
        $form = $this->factory->create(UniqueKeyType::class, null, ['key_choices' => $this->fields]);

        $formData = [
            'name' => '',
            'key'  => [],
        ];

        $form->submit($formData);
        self::assertFalse($form->isValid());
        self::assertTrue($form->isSynchronized());
    }

    public function testNames(): void
    {
        $type = new UniqueKeyType();
        self::assertEquals('oro_entity_extend_unique_key_type', $type->getName());
    }
}
