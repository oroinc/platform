<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Provider;

/**
 * Tells which email template parameter an entity attribute that the email templates rendering sandbox denies is
 * resolved from, i.e. the parameter a listener of
 * {@see \Oro\Bundle\EmailBundle\Event\EmailTemplateSecurityPolicyViolationEvent} substitutes the attribute with.
 */
class SubstitutableEmailTemplateAttributeProvider
{
    /**
     * @var array<class-string, array<string, string>>|null
     */
    private ?array $attributes = null;

    /**
     * @param iterable<SubstitutableEmailTemplateAttributesInterface> $attributeDeclarations
     */
    public function __construct(
        private readonly iterable $attributeDeclarations
    ) {
    }

    /**
     * Returns the name of the email template parameter the given attribute is substituted from,
     * or NULL when the attribute is not substitutable.
     */
    public function getSubstitutionTemplateParameter(string $entityClass, string $attribute): ?string
    {
        foreach ($this->getAttributes() as $declaredClass => $declaredAttributes) {
            if (isset($declaredAttributes[$attribute]) && is_a($entityClass, $declaredClass, true)) {
                return $declaredAttributes[$attribute];
            }
        }

        return null;
    }

    /**
     * @return array<class-string, array<string, string>> Attribute name to email template parameter name,
     *                                                    grouped by the entity class that declares the attribute.
     */
    public function getAttributes(): array
    {
        if (null === $this->attributes) {
            $this->attributes = [];
            foreach ($this->attributeDeclarations as $attributeDeclaration) {
                foreach ($attributeDeclaration->getSubstitutableEmailTemplateAttributes() as $class => $attributes) {
                    // The declaration that comes first wins, so that a decorating declaration cannot be overridden.
                    $this->attributes[$class] = ($this->attributes[$class] ?? []) + $attributes;
                }
            }
        }

        return $this->attributes;
    }
}
