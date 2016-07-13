<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;

use Doctrine\Common\Annotations\AnnotationReader;

use Oro\Bundle\EntityExtendBundle\Form\Type\UniqueKeyType;

class UniqueKeyTypeTest extends TypeTestCase
{
    /**
     * @var UniqueKeyType
     */
    protected $type;

    protected function setUp()
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping(new AnnotationReader())
            ->getValidator();

        $this->factory =
            Forms::createFormFactoryBuilder()
                ->addExtensions([new ValidatorExtension($validator)])
                ->getFormFactory();

        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->builder = new FormBuilder(null, null, $this->dispatcher, $this->factory);

        $fields = [
            'firstName' => 'First Name',
            'lastName'  => 'Last Name',
            'email'     => 'Email',
        ];

        $this->type = new UniqueKeyType($fields);
    }

    public function testType()
    {
        $formData = array(
            'name' => 'test',
            'key'  => array('firstName', 'lastName', 'email')
        );

        $form = $this->factory->create($this->type);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
    }

    public function testSubmitNotValidData()
    {
        $form = $this->factory->create($this->type);

        $formData = array(
            'name' => '',
            'key'  => []
        );

        $form->submit($formData);
        $this->assertFalse($form->isValid());
    }

    public function testNames()
    {
        $this->assertEquals('oro_entity_extend_unique_key_type', $this->type->getName());
    }
}
