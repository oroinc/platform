<?php

namespace Oro\Component\Testing\Unit\Form\Type\Stub;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType as BaseEntityIdentifierType;

class EntityIdentifierType extends EntityType
{
    /**
     * @param array $choices
     */
    public function __construct(array $choices)
    {
        parent::__construct($choices, BaseEntityIdentifierType::NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choice_list' => $this->choiceList,
                'class' => '',
            ]
        );
    }
}
