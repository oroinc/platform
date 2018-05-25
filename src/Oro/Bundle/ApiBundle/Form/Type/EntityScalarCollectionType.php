<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\EventListener\MergeDoctrineCollectionListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * The form type for a collection of manageable entities.
 * Usually this form type is used if an association should be represented as a field in Data API.
 * @see \Oro\Bundle\ApiBundle\Request\DataType::isAssociationAsField
 */
class EntityScalarCollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new MergeDoctrineCollectionListener());
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ScalarCollectionType::class;
    }
}
