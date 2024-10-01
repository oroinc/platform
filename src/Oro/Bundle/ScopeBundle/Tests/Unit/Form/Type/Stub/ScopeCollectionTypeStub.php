<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScopeCollectionTypeStub extends ScopeCollectionType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entry_type' => ScopeType::NAME,
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }
}
