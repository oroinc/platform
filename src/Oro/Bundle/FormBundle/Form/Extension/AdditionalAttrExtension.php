<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds data-ftid and data-name attributes to form fields and generates random IDs.
 */
class AdditionalAttrExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array('random_id' => true));
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (!empty($options['random_id']) && isset($view->vars['id'])) {
            $view->vars['attr'] = isset($view->vars['attr']) ? $view->vars['attr'] : [];
            $view->vars['attr']['data-ftid'] = $view->vars['id'];
            $view->vars['id'] .= uniqid('-uid-');
        }
        if (isset($view->vars['name'])) {
            $fieldPrefix = $view->parent ? 'field__' : 'form__';
            $fieldName = $this->canonizeFieldName($view->vars['name']);
            $view->vars['attr']['data-name'] = $fieldPrefix . $fieldName;
        }
    }

    /**
     * @param string $name
     * @return string
     */
    private function canonizeFieldName($name)
    {
        $name = preg_replace('/[A-Z]/', '-$0', $name);
        $name = str_replace('_', '-', $name);

        return strtolower(trim($name, '-'));
    }
}
