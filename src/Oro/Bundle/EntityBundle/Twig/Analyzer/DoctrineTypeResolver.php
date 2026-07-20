<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\Analyzer;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Symfony\Component\String\UnicodeString;
use Twig\Template;

/**
 * Resolves attribute accesses via Doctrine ORM metadata - checks associations and mapped fields.
 */
class DoctrineTypeResolver implements TypeResolverInterface
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
    ) {
    }

    #[\Override]
    public function resolve(string $className, string $attributeName, string $twigCallType): ?ResolvedAccess
    {
        if ($twigCallType === Template::ARRAY_CALL) {
            return null;
        }

        $entityManager = $this->doctrine->getManagerForClass($className);
        if (!$entityManager) {
            return null;
        }

        $classMetadata = $entityManager->getClassMetadata($className);

        if ($twigCallType === Template::METHOD_CALL) {
            if (!str_starts_with($attributeName, 'get')) {
                // It is not a getter, cannot extract property name.
                return null;
            }

            // Extract property name from the getter name and resolve it handling
            // both camelCase (e.g. passwordExpiresAt) and snake_case (e.g. password_expires_at) variants.
            $propertyName = lcfirst(substr($attributeName, 3));
            $resolvedProperty = $this->resolvePropertyName($className, $propertyName);
            if ($resolvedProperty === null) {
                // Property extracted from the getter name does not exist.
                return null;
            }

            $accessType = TemplateAccessEntry::ACCESS_TYPE_METHOD;
            $resolvedAttribute = $attributeName;
        } else {
            $resolvedProperty = $this->resolvePropertyName($className, $attributeName) ?? $attributeName;
            $resolvedAttribute = $resolvedProperty;
            $accessType = TemplateAccessEntry::ACCESS_TYPE_PROPERTY;
        }

        if ($classMetadata->hasAssociation($resolvedProperty)) {
            $targetClass = $classMetadata->getAssociationTargetClass($resolvedProperty);
            $isCollection = $classMetadata->isCollectionValuedAssociation($resolvedProperty);

            return new ResolvedAccess(
                attributeName: $resolvedAttribute,
                accessType: $accessType,
                entityClass: $targetClass,
                isCollection: $isCollection,
            );
        }

        return new ResolvedAccess(
            attributeName: $resolvedAttribute,
            accessType: $accessType,
            entityClass: null,
        );
    }

    /**
     * Returns the actual property name on $className that matches $propertyName,
     * trying the camelCase and snake_case variants when the original name is not found.
     */
    private function resolvePropertyName(string $className, string $propertyName): ?string
    {
        $entityReflection = new EntityReflectionClass($className);

        if ($entityReflection->hasProperty($propertyName)) {
            return $propertyName;
        }

        $camelCase = $this->toCamelCase($propertyName);
        if ($camelCase !== $propertyName && $entityReflection->hasProperty($camelCase)) {
            return $camelCase;
        }

        $snakeCase = $this->toSnakeCase($propertyName);
        if ($snakeCase !== $propertyName && $entityReflection->hasProperty($snakeCase)) {
            return $snakeCase;
        }

        return null;
    }

    /**
     * Converts a snake_case string to camelCase. E.g. `password_expires_at` → `passwordExpiresAt`.
     */
    private function toCamelCase(string $name): string
    {
        return (new UnicodeString($name))->camel()->toString();
    }

    /**
     * Converts a camelCase string to snake_case. E.g. `passwordExpiresAt` → `password_expires_at`.
     */
    private function toSnakeCase(string $name): string
    {
        return (new UnicodeString($name))->snake()->toString();
    }
}
