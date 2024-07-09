<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Abstract Gedmo translation repository for translation dictionaries.
 * It can speed up translation updating process.
 */
abstract class AbstractTranslationRepository extends TranslationRepository implements TranslationRepositoryInterface
{
    protected function doUpdateTranslations(string $className, string $fieldName, array $data, string $locale): void
    {
        if (!$data) {
            return;
        }

        $connection = $this->getEntityManager()->getConnection();
        $connection->beginTransaction();

        try {
            $qb = $this->createQueryBuilder('entity');
            $qb->select('entity.id', 'entity.foreignKey', 'entity.content')
                ->where(
                    $qb->expr()->in('entity.foreignKey', ':foreignKey'),
                    $qb->expr()->eq('entity.locale', ':locale'),
                    $qb->expr()->eq('entity.objectClass', ':objectClass'),
                    $qb->expr()->eq('entity.field', ':field')
                )
                ->setParameter('foreignKey', array_keys($data))
                ->setParameter('locale', $locale)
                ->setParameter('objectClass', $className)
                ->setParameter('field', $fieldName);

            $result = $qb->getQuery()->getArrayResult();
            $tableName = $this->getClassMetadata()->getTableName();

            foreach ($result as $trans) {
                $value = $data[$trans['foreignKey']];
                unset($data[$trans['foreignKey']]);

                if ($trans['content'] === $value) {
                    continue;
                }

                $connection->update(
                    $tableName,
                    ['content' => $value],
                    ['id' => $trans['id']]
                );
            }

            if ($data) {
                $params = [];
                foreach ($data as $combinedCode => $value) {
                    $params[] = $combinedCode;
                    $params[] = $locale;
                    $params[] = $className;
                    $params[] = $fieldName;
                    $params[] = $value;
                }

                $sql = sprintf(
                    'INSERT INTO %s (foreign_key, locale, object_class, field, content) VALUES %s',
                    $tableName,
                    implode(', ', array_fill(0, count($data), '(?, ?, ?, ?, ?)'))
                );

                $connection->executeQuery($sql, $params);
            }

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();

            throw $e;
        }
    }

    protected function doGetAllIdentities(string $className, string $identityField): array
    {
        $alias = 'entity';
        $result = $this->_em->createQueryBuilder()
            ->select(QueryBuilderUtil::getField($alias, $identityField))
            ->from($className, $alias)
            ->getQuery()
            ->getScalarResult();

        return array_column($result, $identityField);
    }

    protected function doUpdateDefaultTranslations(
        string $className,
        string $valueFieldName,
        string $criteriaFieldName,
        string $criteriaColumnName,
        array $data
    ): void {
        if (!$data) {
            return;
        }

        $alias = 'entity';
        $connection = $this->_em->getConnection();
        $connection->beginTransaction();

        $valueField = QueryBuilderUtil::getField($alias, $valueFieldName);
        $criteriaField = QueryBuilderUtil::getField($alias, $criteriaFieldName);

        try {
            $qb = $this->_em->createQueryBuilder();
            $qb->select($valueField, $criteriaField)
                ->from($className, $alias)
                ->where($qb->expr()->in($criteriaField, ':param'))
                ->setParameter('param', array_keys($data));

            $result = $qb->getQuery()->getArrayResult();

            foreach ($result as $type) {
                $value = $data[$type[$criteriaFieldName]];
                $updateData = [$valueFieldName => $value];
                $criteria = [$criteriaColumnName => $type[$criteriaFieldName]];

                if ($type[$criteriaFieldName] !== $value) {
                    $connection->update(
                        $this->getEntityManager()->getClassMetadata($className)->getTableName(),
                        $updateData,
                        $criteria
                    );
                }
            }

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();

            throw $e;
        }
    }

    /**
     * @param string $languageCode
     * @param string $domain
     *
     * @return array [['key' => '...', 'value' => '...'], ...]
     */
    public function findDomainTranslations(
        string $languageCode,
        string $domain
    ): array {
        $qb = $this->_em->createQueryBuilder()
            ->from(Translation::class, 't');
        $qb->distinct()
            ->select('k.key, t.value')
            ->join('t.language', 'l')
            ->join('t.translationKey', 'k')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('l.code', ':code'),
                    $qb->expr()->gt('t.scope', ':scope'),
                    $qb->expr()->eq('k.domain', ':domain')
                )
            )
            ->setParameter('code', $languageCode, Types::STRING)
            ->setParameter('domain', $domain, Types::STRING)
            ->setParameter('scope', Translation::SCOPE_SYSTEM, Types::INTEGER);

        return $qb->getQuery()->getArrayResult();
    }
}
