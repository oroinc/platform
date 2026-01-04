<?php

namespace Oro\Bundle\UIBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityTreeSelectType;
use Oro\Bundle\UIBundle\Model\TreeItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type provides functionality to select an existing tree
 */
class TreeSelectType extends AbstractType
{
    public const NAME = 'oro_ui_tree_select';

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['treeOptions'] = [
            'view' => $options['page_component_module'],
            'key' => $options['tree_key'],
            'data' => $options['tree_data'],
            'nodeId' => $form->getData() ? $form->getData()->getKey() : null,
            'fieldSelector' => '#' . $view->vars['id']
        ];
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['tree_data', 'tree_key']);

        $resolver->setDefault('data_class', TreeItem::class);
        $resolver->setDefault(
            'page_component_module',
            'oroform/js/app/components/entity-tree-select-form-type-view'
        );

        $resolver->setAllowedTypes('tree_data', ['array']);
        $resolver->setAllowedTypes('tree_key', ['string']);
    }

    public function getName()
    {
        return self::NAME;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return EntityTreeSelectType::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return HiddenType::class;
    }
}
