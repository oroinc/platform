<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Stub;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityIdentifierType extends EntityType
{
    /**
     * @param array $choices
     */
    public function __construct(array $choices)
    {
        parent::__construct($choices, 'oro_entity_identifier');
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                'class' => '',
            ]
        );
    }
}
