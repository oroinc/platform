<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\EmbeddedFormBundle\Form\Type\AvailableEmbeddedFormType;
use Oro\Bundle\EmbeddedFormBundle\Form\Type\EmbeddedFormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmbeddedFormTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldBuildForm()
    {
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->exactly(5))
            ->method('add')
            ->withConsecutive(
                ['title', TextType::class],
                ['formType', AvailableEmbeddedFormType::class],
                ['css', TextareaType::class],
                ['successMessage', TextareaType::class],
                ['allowedDomains', TextareaType::class]
            )
            ->willReturnSelf();

        $formType = new EmbeddedFormType();
        $formType->buildForm($builder, []);
    }

    public function testShouldConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => EmbeddedForm::class]);

        $formType = new EmbeddedFormType();
        $formType->configureOptions($resolver);
    }

    public function testShouldReturnFormName()
    {
        $formType = new EmbeddedFormType();

        $this->assertEquals('embedded_form', $formType->getName());
    }
}
