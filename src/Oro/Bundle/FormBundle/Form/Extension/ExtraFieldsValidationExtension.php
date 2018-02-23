<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExtraFieldsValidationExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;

    const EXTRA_FIELDS_MESSAGE = 'oro.form.extra_fields';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['extra_fields_message' => self::EXTRA_FIELDS_MESSAGE]);
    }
}
