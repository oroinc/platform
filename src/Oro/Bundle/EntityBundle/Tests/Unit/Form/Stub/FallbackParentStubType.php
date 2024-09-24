<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\Stub;

use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FallbackParentStubType extends AbstractType
{
    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'fallback_parent_stub';
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('fallback');
        $resolver->setDefaults(['data_class' => FallbackParentStub::class]);
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('valueWithFallback', EntityFieldFallbackValueType::class, $options['fallback']);
    }
}
