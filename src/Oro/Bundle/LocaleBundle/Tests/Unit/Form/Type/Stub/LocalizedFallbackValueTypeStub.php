<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizedFallbackValueTypeStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'stub_localized_fallback_value';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('string', TextType::class)
            ->add('text', TextType::class)
            ->add('localization', EntityTypeStub::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => LocalizedFallbackValue::class]);
    }
}
