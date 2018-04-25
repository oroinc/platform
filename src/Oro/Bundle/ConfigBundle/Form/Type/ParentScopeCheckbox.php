<?php

namespace Oro\Bundle\ConfigBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParentScopeCheckbox extends AbstractType
{
    const NAME = 'oro_config_parent_scope_checkbox_type';

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CheckboxType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['required' => false, 'label' => 'oro.config.system_configuration.use_default']);
    }
}
