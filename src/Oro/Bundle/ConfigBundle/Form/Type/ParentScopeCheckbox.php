<?php

namespace Oro\Bundle\ConfigBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ParentScopeCheckbox extends AbstractType
{
    const NAME = 'oro_config_parent_scope_checkbox_type';

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
    public function getParent()
    {
        return 'checkbox';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['required' => false, 'label' => 'Use default']);
    }
}
