<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\Analyzer;

/**
 * Resolves a class attribute access to determine the access type (property vs method)
 * and the resulting class type for further chaining.
 */
interface TypeResolverInterface
{
    /**
     * Resolves an attribute access on the given class.
     *
     * @param string $className The FQCN of the object class
     * @param string $attributeName The attribute or method name to access
     * @param string $twigCallType One of Template::ANY_CALL, Template::METHOD_CALL, Template::ARRAY_CALL
     *
     * @return ResolvedAccess|null Resolved access info, or null if this resolver cannot handle the access
     */
    public function resolve(string $className, string $attributeName, string $twigCallType): ?ResolvedAccess;
}
