<?php

namespace Oro\Bundle\TranslationBundle\Extension;

class TranslationContextResolver implements TranslationContextResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve($id)
    {
        return 'UI Label';
    }
}
