<?php

namespace Oro\Bundle\TranslationBundle\Extension;

/**
 * The extensions interface for resolving Translation Context by translation key
 */
interface TranslationContextResolverInterface
{
    /**
     * @param string $id
     * @return string|null
     */
    public function resolve($id);
}
