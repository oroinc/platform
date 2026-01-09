<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides extra fields validation message configuration for forms.
 */
class ExtraFieldsValidationExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;

    public const EXTRA_FIELDS_MESSAGE = 'oro.form.extra_fields';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['extra_fields_message' => self::EXTRA_FIELDS_MESSAGE]);
    }
}
