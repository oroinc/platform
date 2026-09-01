<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\JoinAssociationDeclaration;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\SelectStatement;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

/**
 * Detects owned entities in a query with an empty organization field name configuration.
 *
 * Prevents AclHelper from generating invalid SQL (e.g., "WHERE p0_. = 1") caused by
 * incomplete entity ownership metadata (see OwnershipMetadata::getOrganizationFieldName()).
 */
final class DetectEntitiesWithoutOrganizationField
{
    public function __construct(
        private readonly OwnershipMetadataProviderInterface $ownershipMetadataProvider
    ) {
    }

    /**
     * @return string[]
     */
    public function detect(Query $query): array
    {
        $queriedClasses = $this->getQueriedEntityClasses($query);

        return \array_values(\array_filter($queriedClasses, $this->isOwnedWithoutOrganizationField(...)));
    }

    private function isOwnedWithoutOrganizationField(string $entityClass): bool
    {
        $metadata = $this->ownershipMetadataProvider->getMetadata($entityClass);

        return $metadata->hasOwner() && '' === $metadata->getOrganizationFieldName();
    }

    /**
     * Subqueries are not inspected.
     *
     * @return string[]
     */
    private function getQueriedEntityClasses(Query $query): array
    {
        $ast = $query->getAST();
        if (!$ast instanceof SelectStatement) {
            return [];
        }

        $aliasToClass = [];
        foreach ($ast->fromClause->identificationVariableDeclarations as $declaration) {
            $aliasToClass += $this->getDeclaredEntityClasses($query->getEntityManager(), $declaration);
        }

        return \array_values(\array_unique($aliasToClass));
    }

    /**
     * @return array<string, string> alias => entity class for one FROM entry and its joins
     */
    private function getDeclaredEntityClasses(
        EntityManagerInterface $em,
        IdentificationVariableDeclaration $declaration
    ): array {
        $root = $declaration->rangeVariableDeclaration;
        $aliasToClass = [$root->aliasIdentificationVariable => $root->abstractSchemaName];

        foreach ($declaration->joins as $join) {
            $joined = $join->joinAssociationDeclaration;
            $joinedClass = $this->resolveJoinedEntityClass($em, $joined, $aliasToClass);
            if (null !== $joinedClass) {
                $aliasToClass[$joined->aliasIdentificationVariable] = $joinedClass;
            }
        }

        return $aliasToClass;
    }

    /**
     * "JOIN Some\Entity e" carries the class itself; "JOIN alias.association e" resolves via association metadata.
     *
     * @param array<string, string> $aliasToClass
     */
    private function resolveJoinedEntityClass(EntityManagerInterface $em, Node $joined, array $aliasToClass): ?string
    {
        if ($joined instanceof RangeVariableDeclaration) {
            return $joined->abstractSchemaName;
        }

        if (!$joined instanceof JoinAssociationDeclaration) {
            return null;
        }

        $path = $joined->joinAssociationPathExpression;
        $sourceClass = $aliasToClass[$path->identificationVariable] ?? null;

        return null !== $sourceClass
            ? $em->getClassMetadata($sourceClass)->getAssociationTargetClass($path->associationField)
            : null;
    }
}
