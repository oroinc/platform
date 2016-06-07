<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;

class LocalizedFallbackValueCollectionTypeStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
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
            'type' => 'text',
            'options' => [],
            'allow_add' => true,
            'allow_delete' => true,
        ]);

        $resolver->setNormalizer('type', function () {
            return new LocalizedFallbackValueTypeStub();
        });

        $resolver->setNormalizer('options', function () {
            return [];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'collection';
    }
}
