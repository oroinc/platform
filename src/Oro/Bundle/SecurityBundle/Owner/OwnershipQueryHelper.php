<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

class OwnershipQueryHelper
{
    /** @var OwnershipMetadataProviderInterface */
    private $ownershipMetadataProvider;

    /** @var EntityClassResolver */
    private $entityClassResolver;

    /**
     * @param OwnershipMetadataProviderInterface $ownershipMetadataProvider
     * @param EntityClassResolver                $entityClassResolver
     */
    public function __construct(
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        EntityClassResolver $entityClassResolver
    ) {
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * Adds ownership related fields to a query builder.
     * These fields are added only if the identifier field exists in SELECT clause.
     *
     * @param QueryBuilder  $qb
     * @param callable|null $filter function ($entityClass, $entityAlias) : bool
     *
     * @return array [entity alias => [
     *                      entity class,
     *                      entity id field alias,
     *                      organization id field alias,
     *                      owner id field alias
     *                  ],
     *                  ...
     *              ]
     */
    public function addOwnershipFields(QueryBuilder $qb, $filter = null)
    {
        $result = [];

        $queryAliases = $this->collectEntityAliases($qb, $filter);
        foreach ($queryAliases as $entityAlias => $data) {
            list($entityClass, $idFieldAlias) = $data;
            $metadata = $this->ownershipMetadataProvider->getMetadata($entityClass);
            if (!$metadata->hasOwner()) {
                continue;
            }

            $organizationFieldName = $metadata->getOrganizationFieldName();
            $organizationIdFieldAlias = null;
            if ($organizationFieldName) {
                $organizationIdFieldAlias = $entityAlias . '_organization_id';
                $this->addOwnershipField(
                    $qb,
                    $this->getIdentityExpr($entityAlias, $organizationFieldName, $organizationIdFieldAlias)
                );
            }

            $ownerFieldName = $metadata->getOwnerFieldName();
            $ownerIdFieldAlias = null;
            if ($ownerFieldName) {
                $ownerIdFieldAlias = $entityAlias . '_owner_id';
                $this->addOwnershipField(
                    $qb,
                    $this->getIdentityExpr($entityAlias, $ownerFieldName, $ownerIdFieldAlias)
                );
            }

            $result[$entityAlias] = [$entityClass, $idFieldAlias, $organizationIdFieldAlias, $ownerIdFieldAlias];
        }

        return $result;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $fieldExpr
     */
    private function addOwnershipField(QueryBuilder $qb, $fieldExpr)
    {
        /** @var Expr\Select[] $selects */
        $selects = $qb->getDQLPart('select');
        foreach ($selects as $select) {
            $parts = $select->getParts();
            foreach ($parts as $part) {
                if (false !== strpos($part, $fieldExpr)) {
                    return;
                }
            }
        }

        $qb->addSelect($fieldExpr);
    }

    /**
     * @param string $entityAlias
     * @param string $fieldName
     * @param string $fieldAlias
     *
     * @return string
     */
    private function getIdentityExpr($entityAlias, $fieldName, $fieldAlias)
    {
        return QueryBuilderUtil::sprintf('IDENTITY(%s.%s) AS %s', $entityAlias, $fieldName, $fieldAlias);
    }

    /**
     * @param QueryBuilder  $qb
     * @param callable|null $filter function ($entityClass, $entityAlias) : bool
     *
     * @return array [entity alias => [entity class, id field alias], ...]
     */
    private function collectEntityAliases(QueryBuilder $qb, $filter = null)
    {
        $queryAliases = [];
        /** @var Expr\From[] $fromParts */
        $fromParts = $qb->getDQLPart('from');
        foreach ($fromParts as $fromPart) {
            $entityClass = $this->entityClassResolver->getEntityClass($fromPart->getFrom());
            $entityAlias = $fromPart->getAlias();
            $idFieldAlias = $this->getIdentifierFieldAlias($entityAlias, $entityClass, $qb);
            if ($idFieldAlias && (null === $filter || $filter($entityClass, $entityAlias))) {
                $queryAliases[$entityAlias] = [$entityClass, $idFieldAlias];
            }
        }

        return $queryAliases;
    }

    /**
     * @param string       $entityAlias
     * @param string       $entityClass
     * @param QueryBuilder $qb
     *
     * @return string|null
     */
    private function getIdentifierFieldAlias($entityAlias, $entityClass, QueryBuilder $qb)
    {
        $idFieldNames = $qb->getEntityManager()
            ->getClassMetadata($entityClass)
            ->getIdentifierFieldNames();
        if (count($idFieldNames) !== 1) {
            return false;
        }

        $idFieldExpr = sprintf('%s.%s', $entityAlias, $idFieldNames[0]);

        /** @var Expr\Select[] $selects */
        $selects = $qb->getDQLPart('select');
        foreach ($selects as $select) {
            $parts = $select->getParts();
            foreach ($parts as $part) {
                $idFieldAlias = $this->getEntityIdentifierFieldAlias($part, $idFieldExpr, $idFieldNames[0]);
                if ($idFieldAlias) {
                    return $idFieldAlias;
                }
            }
        }

        return null;
    }

    /**
     * @param string $selectExpr
     * @param string $idFieldExpr
     * @param string $idFieldName
     *
     * @return string|null
     */
    private function getEntityIdentifierFieldAlias($selectExpr, $idFieldExpr, $idFieldName)
    {
        $result = null;
        $selectExprLength = strlen($selectExpr);
        $idFieldExprLength = strlen($idFieldExpr);

        $offset = 0;
        while (false !== $pos = strpos($selectExpr, $idFieldExpr, $offset)) {
            $beginExprPos = strrpos($selectExpr, ',', -($selectExprLength - $pos - 1));
            $endExprPos = strpos($selectExpr, ',', $pos + $idFieldExprLength);
            if (false === $beginExprPos) {
                $beginExprPos = -1;
            }
            if (false === $endExprPos) {
                $endExprPos = $selectExprLength + 1;
            }

            $expr = trim(substr($selectExpr, $beginExprPos + 1, $endExprPos - $beginExprPos - 1));

            if (strlen($expr) === $idFieldExprLength) {
                $result = $idFieldName;
                break;
            }
            if (0 === strpos($expr, $idFieldExpr)) {
                $asExpr = strtolower(trim(substr($expr, $idFieldExprLength)));
                if (0 === strpos($asExpr, 'as ')) {
                    $result = trim(substr($expr, strpos(strtolower($expr), ' as ') + 4));
                    break;
                }
            }

            $offset = $pos + 1;
        }

        return $result;
    }
}
