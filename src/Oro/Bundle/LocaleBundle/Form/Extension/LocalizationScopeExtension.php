<?php

namespace Oro\Bundle\LocaleBundle\Form\Extension;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizationSelectType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class LocalizationScopeExtension extends AbstractTypeExtension
{
    const SCOPE_FIELD = 'localization';

    /**
     * @var string
     */
    protected $extendedType;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (array_key_exists(self::SCOPE_FIELD, $options['scope_fields'])) {
            $builder->add(
                self::SCOPE_FIELD,
                LocalizationSelectType::class,
                [
                    'label' => 'oro.locale.localization.entity_label',
                    'create_form_route' => null,
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ScopeType::class;
    }
}
