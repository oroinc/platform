<?php

namespace Oro\Bundle\LayoutBundle\Tests\Fixtures;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class UserNameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, ['required' => true, 'label' => 'First Name'])
            ->add('lastName', TextType::class, ['required' => true, 'label' => 'Last Name']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'user_name';
    }
}
