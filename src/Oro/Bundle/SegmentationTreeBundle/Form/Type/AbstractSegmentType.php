<?php
namespace Oro\Bundle\SegmentationTreeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * Type for segment form
 *
 *
 * @abstract
 */
abstract class AbstractSegmentType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('code');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_segmentation_tree';
    }
}
