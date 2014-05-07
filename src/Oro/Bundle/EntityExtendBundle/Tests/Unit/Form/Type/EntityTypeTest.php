<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\DefaultTranslator;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Validator;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityExtendBundle\Form\Type\EntityType;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;

class EntityTypeTest extends TypeTestCase
{
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $validator = new Validator(
            new ClassMetadataFactory(new LoaderChain([])),
            new ConstraintValidatorFactory(),
            new DefaultTranslator()
        );

        $this->factory = Forms::createFormFactoryBuilder()
            ->addTypeExtension(new DataBlockExtension())
            ->addTypeExtension(new FormTypeValidatorExtension($validator))
            ->getFormFactory();

        $this->type = new EntityType(new ExtendDbIdentifierNameGenerator());
    }

    public function testType()
    {
        $formData = array(
            'className' => 'NewEntityClassName'
        );

        $form = $this->factory->create($this->type);
        $form->submit($formData);

        $object = new EntityConfigModel();
        $object->setClassName('NewEntityClassName');

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($object, $form->getData());
    }

    public function testNames()
    {
        $this->assertEquals('oro_entity_extend_entity_type', $this->type->getName());
    }
}
