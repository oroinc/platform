<?php

namespace Oro\Bundle\EntityExtendBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * The enum option repository.
 */
class EnumOptionRepository extends EntityRepository
{
    public function createEnumOption(
        string $enumCode,
        string $internalId,
        string $name,
        int $priority,
        bool $default = false
    ): EnumOptionInterface {
        if (strlen($name) === 0) {
            throw new \InvalidArgumentException('$name must not be empty.');
        }
        if (strlen($internalId) === 0) {
            $internalId = ExtendHelper::buildEnumInternalId($name);
        }
        $id = ExtendHelper::buildEnumOptionId($enumCode, $internalId);
        if (strlen($id) > ExtendHelper::MAX_ENUM_ID_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf(
                    '$id length must be less or equal %d characters. id: %s.',
                    ExtendHelper::MAX_ENUM_ID_LENGTH,
                    $id
                )
            );
        }
        $enumOptionClassName = $this->getClassName();

        return new $enumOptionClassName(
            $enumCode,
            $name,
            $internalId,
            $priority,
            $default
        );
    }

    public function getValuesQueryBuilder(string $enumCode): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e');
        $qb->andWhere('e.enumCode = :enumCode')
            ->setParameter('enumCode', $enumCode)
            ->orderBy($qb->expr()->asc('e.priority'));

        return $qb;
    }

    public function getValues(string $enumCode): array
    {
        return $this->getValuesQueryBuilder($enumCode)->getQuery()->getResult();
    }

    public function getValue($id): ?EnumOptionInterface
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getDefaultValuesQueryBuilder(string $enumCode): QueryBuilder
    {
        $qb = $this->getValuesQueryBuilder($enumCode);
        $qb->andWhere($qb->expr()->eq('e.default', ':default'))
            ->setParameter('default', true);

        return $qb;
    }

    public function getDefaultValues($enumCode): array
    {
        return $this->getDefaultValuesQueryBuilder($enumCode)->getQuery()->getResult();
    }
}
