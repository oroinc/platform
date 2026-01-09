<?php

namespace Oro\Bundle\EntityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Form type for custom entity forms.
 *
 * This form type aggregates block configuration from child form fields and makes it
 * available at the parent form level. It enables custom entity forms to organize
 * fields into logical blocks with shared configuration.
 */
class CustomEntityType extends AbstractType
{
    #[\Override]
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

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'custom_entity_type';
    }
}
