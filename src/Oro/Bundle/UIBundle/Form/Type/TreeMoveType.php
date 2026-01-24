<?php

namespace Oro\Bundle\UIBundle\Form\Type;

use Oro\Bundle\UIBundle\Form\DataTransformer\TreeItemIdTransformer;
use Oro\Bundle\UIBundle\Model\TreeCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for handling tree item move operations.
 *
 * Provides a form for moving tree items to a new target location within the tree hierarchy.
 * Includes fields for selecting the target location and optionally creating a redirect
 * when the item's slug changes due to the move operation.
 */
class TreeMoveType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'target',
                TreeSelectType::class,
                [
                    'tree_data' => $options['tree_data'],
                    'tree_key' => 'move',
                    'label' => 'oro.ui.jstree.move.target.label',
                ]
            )
            ->add(
                'createRedirect',
                CheckboxType::class,
                [
                    'label' => 'oro.ui.jstree.move.confirm_slug_change.title',
                    'data' => true,
                ]
            );

        $builder->get('target')->addModelTransformer(new TreeItemIdTransformer($options['tree_items']));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['tree_items', 'tree_data']);

        $resolver->setDefaults([
            'data_class' => TreeCollection::class,
        ]);

        $resolver->setAllowedTypes('tree_items', ['array']);
        $resolver->setAllowedTypes('tree_data', ['array']);
    }
}
