<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Extension\DateTimeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * General DateTime form type.
 * Makes default format with timezone.
 */
class OroDateTimeType extends OroDateType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(['format' => DateTimeExtension::HTML5_FORMAT_WITH_TIMEZONE, 'html5' => false]);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return DateTimeType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_datetime';
    }
}
