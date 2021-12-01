<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StubType extends AbstractType
{
    public const FIELD_1 = 'field1';
    public const FIELD_2 = 'field2';
    public const REQUIRED_OPTION = 'required_option';
    public const NAME = 'stub_form_type';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(self::FIELD_1, TextType::class)
            ->add(self::FIELD_2, TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'scope' => null,
                'ownership_disabled' => true
            ]
        );
        $resolver->setRequired(self::REQUIRED_OPTION);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
