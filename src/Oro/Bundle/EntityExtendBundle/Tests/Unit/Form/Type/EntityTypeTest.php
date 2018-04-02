<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityExtendBundle\Form\Type\EntityType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class EntityTypeTest extends FormIntegrationTestCase
{
    protected $type;

    protected function setUp()
    {
        $validator = new RecursiveValidator(
            new ExecutionContextFactory(new IdentityTranslator()),
            new LazyLoadingMetadataFactory(new LoaderChain([])),
            new ConstraintValidatorFactory()
        );

        $this->factory = Forms::createFormFactoryBuilder()
            ->addTypeExtension(new DataBlockExtension())
            ->addTypeExtension(new FormTypeValidatorExtension($validator))
            ->getFormFactory();

        $this->type = new EntityType(new ExtendDbIdentifierNameGenerator());
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    EntityType::class => $this->type
                ],
                [
                    FormType::class => [
                        new DataBlockExtension()
                    ]
                ]
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testType()
    {
        $formData = array(
            'className' => 'NewEntityClassName'
        );

        $form = $this->factory->create(EntityType::class);
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
