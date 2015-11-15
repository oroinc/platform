<?php

namespace Oro\Bundle\FlexibleEntityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Collection item
 */
abstract class CollectionItemAbstract extends AbstractType
{
    /**
     * Returns choices array form type select box
     *
     * @return mixed
     */
    abstract public function getTypesArray();

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(
                array(
                    'data_class'    => 'Oro\Bundle\FlexibleEntityBundle\Entity\Collection',
                    'required'      => false
                )
            );
    }
}
