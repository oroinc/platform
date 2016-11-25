<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StubType extends AbstractType
{
    const FIELD_1 = 'field1';
    const FIELD_2 = 'field2';
    const REQUIRED_OPTION = 'required_option';
    const NAME = 'stub_form_type';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(self::FIELD_1, 'text')
            ->add(self::FIELD_2, 'text');
    }

    /**
     * @param OptionsResolver $resolver
     */
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
    public function getName()
    {
        return self::NAME;
    }
}
