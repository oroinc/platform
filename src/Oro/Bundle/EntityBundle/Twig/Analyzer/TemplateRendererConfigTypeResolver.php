<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\Analyzer;

use Twig\Template;

/**
 * Resolves attribute accesses on entity classes by looking up the variable in the entity variable
 * providers collected by {@see \Oro\Bundle\EntityBundle\Twig\Sandbox\VariablesProvider}.
 *
 * Supports dotted virtual variable names (e.g. `url.view`) provided by processors such as
 * {@see \Oro\Bundle\EmailBundle\Provider\EntityRouteVariablesProvider}.
 * When `attributeName` is a namespace prefix of such a dotted name (e.g. `url`), this resolver
 * returns a {@see ResolvedAccess} with `skipAccessEntry = true` so that
 * {@see AccessNodeVisitor} suppresses the intermediate step and prevents false positive security-policy violations.
 */
class TemplateRendererConfigTypeResolver implements TypeResolverInterface
{
    public function __construct(
        private readonly EntityVariablesProvider $entityVariablesProvider,
    ) {
    }

    #[\Override]
    public function resolve(string $className, string $attributeName, string $twigCallType): ?ResolvedAccess
    {
        if (Template::ARRAY_CALL === $twigCallType) {
            return null;
        }

        if (Template::METHOD_CALL === $twigCallType) {
            if (!str_starts_with($attributeName, 'get')) {
                // Not a getter method — cannot map to a known virtual variable.
                return null;
            }

            // Extract virtual variable name from the getter name, like DoctrineTypeResolver does.
            $resolvedProperty = lcfirst(substr($attributeName, 3));
            $accessType = TemplateAccessEntry::ACCESS_TYPE_METHOD;
            $resolvedAttribute = $attributeName;
        } else {
            $resolvedProperty = $attributeName;
            $resolvedAttribute = $attributeName;
            $accessType = TemplateAccessEntry::ACCESS_TYPE_PROPERTY;
        }

        $classVars = $this->entityVariablesProvider->getClassVariables($className);
        if ($classVars === null) {
            return null;
        }

        if (array_key_exists($resolvedProperty, $classVars)) {
            return new ResolvedAccess(
                attributeName: $resolvedAttribute,
                accessType: $accessType,
                entityClass: $classVars[$resolvedProperty],
            );
        }

        // Check whether $varName is a namespace prefix of a dotted virtual variable (e.g. "url" for "url.view").
        // Such variables are provided by processors (e.g. EntityRouteVariablesProvider) and are handled
        // by EntityVariablesTemplateProcessor before Twig renders the template, so the intermediate
        // attribute access (e.g. "url") is never actually evaluated at runtime.
        // Return skipAccessEntry=true so AccessNodeVisitor suppresses the step - this prevents
        // DoctrineTypeResolver from generating a false positive access entry for the non-existent property.
        $prefix = $resolvedProperty . '.';
        foreach ($classVars as $knownVarName => $_) {
            if (str_starts_with($knownVarName, $prefix)) {
                return new ResolvedAccess(
                    attributeName: $resolvedAttribute,
                    accessType: $accessType,
                    entityClass: null,
                    skipAccessEntry: true,
                );
            }
        }

        return null;
    }
}
