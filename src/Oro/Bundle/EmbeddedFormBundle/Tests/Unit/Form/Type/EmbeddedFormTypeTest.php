<?php
namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmbeddedFormBundle\Form\Type\AvailableEmbeddedFormType;
use Oro\Bundle\EmbeddedFormBundle\Form\Type\EmbeddedFormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmbeddedFormTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function shouldBeConstructed()
    {
        new EmbeddedFormType();
    }

    /**
     * @test
     */
    public function shouldBuildForm()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject | FormBuilderInterface $builder */
        $builder = $this->createMock('\Symfony\Component\Form\FormBuilder');
        $builder->expects($this->at(0))
            ->method('add')
            ->with('title', TextType::class)
            ->will($this->returnSelf());
        $builder->expects($this->at(1))
            ->method('add')
            ->with('formType', AvailableEmbeddedFormType::class)
            ->will($this->returnSelf());
        $builder->expects($this->at(2))
            ->method('add')
            ->with('css', TextareaType::class)
            ->will($this->returnSelf());
        $builder->expects($this->at(3))
            ->method('add')
            ->with('successMessage', TextareaType::class)
            ->will($this->returnSelf());

        $formType = new EmbeddedFormType();
        $formType->buildForm($builder, []);
    }

    /**
     * @test
     */
    public function shouldConfigureOptions()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject | OptionsResolver $resolver */
        $resolver = $this->createMock('\Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => 'Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm']);

        $formType = new EmbeddedFormType();
        $formType->configureOptions($resolver);
    }

    /**
     * @test
     */
    public function shouldReturnFormName()
    {
        $formType = new EmbeddedFormType();

        $this->assertEquals('embedded_form', $formType->getName());
    }
}
