<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizedFallbackValueCollectionTypeStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return LocalizedFallbackValueCollectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'field' => 'string',
            'value_class' => LocalizedFallbackValue::class,
            'entry_type' => TextType::class,
            'entry_options' => [],
            'allow_add' => true,
            'allow_delete' => true,
            'use_tabs' => false,
        ]);

        $resolver->setNormalizer('entry_type', function () {
            return LocalizedFallbackValueTypeStub::class;
        });

        $resolver->setNormalizer('entry_options', function (Options $options) {
            return ['data_class' => $options['value_class']];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::class;
    }
}
