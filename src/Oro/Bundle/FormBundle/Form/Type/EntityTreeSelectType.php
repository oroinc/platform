<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type provides functionality to select an existing entity from the tree
 */
class EntityTreeSelectType extends AbstractType
{
    const NAME = 'oro_entity_tree_select';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['tree_data', 'tree_key']);

        $resolver->setDefault(
            'page_component_module',
            'oroform/js/app/components/entity-tree-select-form-type-view'
        );

        $resolver->setDefault(
            'page_component_options',
            []
        );

        $resolver->setNormalizer(
            'multiple',
            function () {
                return false;
            }
        );

        $resolver->setAllowedTypes('tree_data', ['array', 'callable']);
        $resolver->setAllowedTypes('tree_key', ['string']);
        $resolver->setAllowedTypes('page_component_module', ['string']);
        $resolver->setAllowedTypes('page_component_options', ['array']);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (is_callable($options['tree_data'])) {
            $treeData = call_user_func($options['tree_data']);
        } else {
            $treeData = $options['tree_data'];
        }

        $view->vars['treeOptions'] = array_merge([
            'view' => $options['page_component_module'],
            'key' => $options['tree_key'],
            'data' => $treeData,
            'nodeId' => $form->getData() ? $form->getData()->getId() : null,
            'fieldSelector' => '#' . $view->vars['id']
        ], $options['page_component_options']);
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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityIdentifierType::class;
    }
}
