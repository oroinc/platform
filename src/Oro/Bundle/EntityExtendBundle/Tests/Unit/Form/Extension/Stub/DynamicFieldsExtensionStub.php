<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Extension\Stub;

use Oro\Bundle\EntityExtendBundle\Form\Extension\DynamicFieldsOptionsExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DynamicFieldsExtensionStub extends DynamicFieldsOptionsExtension
{
    private array $fieldsConfiguration;

    public function __construct(array $fieldsConfiguration)
    {
        $this->fieldsConfiguration = $fieldsConfiguration;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->fieldsConfiguration as $field) {
            $builder->add($field[0], $field[1] ?? null, $field[2] ?? []);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('is_dynamic_field', false);
    }
}
