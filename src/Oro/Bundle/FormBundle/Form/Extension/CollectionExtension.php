<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds "add_label" option that can be used to specify Add button label to the CollectionType form type.
 */
class CollectionExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [CollectionType::class];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('add_label', 'oro.form.collection.add');
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['add_label'] = $options['add_label'];
    }
}
