<?php

namespace Oro\Bundle\LayoutBundle\Tests\Fixtures;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserNameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', 'text', ['required' => true, 'label' => 'First Name', 'random_id' => false])
            ->add('lastName', 'text', ['required' => true, 'label' => 'Last Name', 'random_id' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'user_name';
    }
}
