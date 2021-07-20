<?php

namespace Oro\Bundle\NavigationBundle\Utils;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Utility\PersisterHelper;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\DoctrineUtils\ORM\ResultSetMappingUtil;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a way to add a request to the navigation history.
 */
class NavigationHistoryLogger
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var string */
    private $navigationHistoryItemClassName;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var TitleServiceInterface */
    private $titleService;

    public function __construct(
        ManagerRegistry $doctrine,
        string $navigationHistoryItemClassName,
        TokenAccessorInterface $tokenAccessor,
        TitleServiceInterface $titleService
    ) {
        $this->doctrine = $doctrine;
        $this->navigationHistoryItemClassName = $navigationHistoryItemClassName;
        $this->tokenAccessor = $tokenAccessor;
        $this->titleService = $titleService;
    }

    public function logRequest(Request $request): void
    {
        $visitedAt = new \DateTime('now', new \DateTimeZone('UTC'));

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass($this->navigationHistoryItemClassName);
        $metadata = $em->getClassMetadata($this->navigationHistoryItemClassName);
        $tableName = $metadata->getTableName();

        $findNavigationHistoryItemCriteria = [
            'url'          => substr($request->getRequestUri(), 0, $this->getUrlLength($metadata)),
            'user'         => $this->tokenAccessor->getUserId(),
            'organization' => $this->tokenAccessor->getOrganizationId()
        ];

        $qb = new SqlQueryBuilder(
            $em,
            ResultSetMappingUtil::createResultSetMapping($em->getConnection()->getDatabasePlatform())
        );
        $item = $this->findNavigationHistoryItem($tableName, $findNavigationHistoryItemCriteria, $metadata, $em);
        if (null === $item) {
            $routeParameters = $request->attributes->get('_route_params');
            unset($routeParameters['id']);
            $entityId = filter_var($request->get('id'), FILTER_VALIDATE_INT);
            if (false !== $entityId) {
                $entityId = (int)$entityId;
            } else {
                $entityId = null;
            }
            $data = $findNavigationHistoryItemCriteria;
            $data['visitCount'] = 1;
            $data['route'] = $request->attributes->get('_route');
            $data['routeParameters'] = $routeParameters;
            $data['entityId'] = $entityId;
            $data['title'] = $this->titleService->getSerialized();
            $qb->insert($tableName);
        } else {
            [$itemId, $visitCount, $title] = $item;
            $data = [
                'visitCount' => $visitCount + 1
            ];
            $currentTitle = $this->titleService->getSerialized();
            if ($title !== $currentTitle) {
                $data['title'] = $currentTitle;
            }
            $qb->update($tableName);
        }
        $data['visitedAt'] = $visitedAt;
        $lastParameterIndex = $this->fillData($qb, $data, $metadata, $em);
        if (null !== $item) {
            $lastParameterIndex++;
            $qb->update($tableName)
                ->where($this->getColumnName('id', $metadata) . ' = ?')
                ->setParameter($lastParameterIndex, $item[0]);
        }

        $qb->getQuery()->execute();
    }

    private function fillData(
        SqlQueryBuilder $qb,
        array $data,
        ClassMetadata $metadata,
        EntityManagerInterface $em
    ): int {
        $isInsert = $qb->getType() === QueryBuilder::INSERT;
        $platform = $em->getConnection()->getDatabasePlatform();
        $parameterIndex = -1;
        foreach ($data as $fieldName => $value) {
            $parameterIndex++;
            $columnName = $this->getColumnName($fieldName, $metadata);
            if ($isInsert) {
                $qb->setValue($columnName, '?');
            } else {
                $qb->set($columnName, '?');
            }
            $type = $this->getTypeOfField($fieldName, $metadata, $em);
            if ($type instanceof Type) {
                $value = $type->convertToDatabaseValue($value, $platform);
                $type = $type->getBindingType();
            }
            $qb->setParameter($parameterIndex, $value, $type);
        }

        return $parameterIndex;
    }

    /**
     * @param string                 $tableName
     * @param array                  $criteria
     * @param ClassMetadata          $metadata
     * @param EntityManagerInterface $em
     *
     * @return array|null [item id, visit count, title]
     */
    private function findNavigationHistoryItem(
        string $tableName,
        array $criteria,
        ClassMetadata $metadata,
        EntityManagerInterface $em
    ): ?array {
        $idColumnName = $this->getColumnName('id', $metadata);
        $visitCountColumnName = $this->getColumnName('visitCount', $metadata);
        $titleColumnName = $this->getColumnName('title', $metadata);
        $selectExpr = $idColumnName . ', ' . $visitCountColumnName . ', ' . $titleColumnName;
        $rsm = ResultSetMappingUtil::createResultSetMapping($em->getConnection()->getDatabasePlatform());
        $qb = new SqlQueryBuilder($em, $rsm);
        $rsm
            ->addScalarResult($idColumnName, $idColumnName, 'integer')
            ->addScalarResult($visitCountColumnName, $visitCountColumnName, 'integer')
            ->addScalarResult($titleColumnName, $titleColumnName, 'text');
        $qb
            ->from($tableName)
            ->select($selectExpr)
            ->setMaxResults(1);
        $platform = $em->getConnection()->getDatabasePlatform();
        $parameterIndex = -1;
        $whereExpr = '';
        foreach ($criteria as $fieldName => $value) {
            $parameterIndex++;
            $type = $this->getTypeOfField($fieldName, $metadata, $em);
            if ($type instanceof Type) {
                $value = $type->convertToDatabaseValue($value, $platform);
                $type = $type->getBindingType();
            }
            if ($whereExpr) {
                $whereExpr .= ' AND ';
            }
            $whereExpr .= $this->getColumnName($fieldName, $metadata) . ' = ?';
            $qb->setParameter($parameterIndex, $value, $type);
        }
        if ($whereExpr) {
            $qb->where($whereExpr);
        }

        $rows = $qb->getQuery()->getArrayResult();
        if (!$rows) {
            return null;
        }

        $row = reset($rows);

        return [$row[$idColumnName], $row[$visitCountColumnName] ?? 0, $row[$titleColumnName]];
    }

    private function getUrlLength(ClassMetadata $metadata): int
    {
        $urlMapping = $metadata->getFieldMapping('url');

        return $urlMapping['length'];
    }

    private function getColumnName(string $fieldName, ClassMetadata $metadata): string
    {
        if ($metadata->hasAssociation($fieldName)) {
            return $metadata->getSingleAssociationJoinColumnName($fieldName);
        }

        return $metadata->getColumnName($fieldName);
    }

    /**
     * @param string                 $fieldName
     * @param ClassMetadata          $metadata
     * @param EntityManagerInterface $em
     *
     * @return mixed|null
     */
    private function getTypeOfField(string $fieldName, ClassMetadata $metadata, EntityManagerInterface $em)
    {
        $targetType = PersisterHelper::getTypeOfField($fieldName, $metadata, $em);
        if ([] === $targetType) {
            return null;
        }

        $type = reset($targetType);
        if (\is_string($type)) {
            $type = Type::getType($type);
        }

        return $type;
    }
}
