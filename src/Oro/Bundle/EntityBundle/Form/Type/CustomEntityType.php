<?php

namespace Oro\Bundle\EntityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class CustomEntityType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $blockConfig = isset($view->vars['block_config']) ? $view->vars['block_config'] : [];
        foreach ($view->children as $child) {
            if (isset($child->vars['block_config'])) {
                $blockConfig = array_merge($blockConfig, $child->vars['block_config']);
                unset($child->vars['block_config']);
            }
        }
        $view->vars['block_config'] = $blockConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'custom_entity_type';
    }
}
