<?php

namespace Oro\Bundle\TranslationBundle\Extension;

use Oro\Bundle\TranslationBundle\Extension\TranslationContextResolverInterface;

class TranslationContextResolver implements TranslationContextResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve($id)
    {
        return 'UI:Label';
    }
}
