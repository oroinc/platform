<?php

namespace Oro\Bundle\DataGridBundle\Form\Type;

use Oro\Bundle\QueryDesignerBundle\Form\Type\SortingChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Form type for datagrid sorting configuration.
 *
 * This form type provides fields for configuring sorting in datagrids, including the field
 * to sort by and the sort direction (ascending or descending).
 */
class GridSortingType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('field', TextType::class)
            ->add('direction', SortingChoiceType::class);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_datagrid_sorting';
    }
}
