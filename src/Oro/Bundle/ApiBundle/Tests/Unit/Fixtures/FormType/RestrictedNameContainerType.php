<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RestrictedNameContainerType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['name_options']['constraints'][] = new Assert\Length(['min' => 5]);

        $builder
            ->add('name', 'text', $options['name_options']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['name_options' => []]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'test_restricted_name_container';
    }
}
