<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Stub;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityIdentifierTypeStub extends EntityTypeStub
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('class', '');
    }
}
