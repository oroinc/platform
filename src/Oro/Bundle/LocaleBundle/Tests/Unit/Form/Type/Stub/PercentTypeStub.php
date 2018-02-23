<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PercentTypeStub extends PercentType
{
    const NAME = 'percent_stub';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(['validation_groups' => ['Default']]);
    }
}
