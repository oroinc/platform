<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TestEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\Bundle\FormBundle\Tests\Unit\Form\Type\Stub\TestEntity',
            'test_option' => 'default_value',
            'validation_groups' => ['Default'],
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'test_entity';
    }
}
