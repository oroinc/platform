<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NameValuePairType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, $options['name_options'])
            ->add('value', TextType::class, $options['value_options']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'name_options'  => [],
                'value_options' => []
            ]
        );
    }


    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'test_name_value_pair';
    }
}
