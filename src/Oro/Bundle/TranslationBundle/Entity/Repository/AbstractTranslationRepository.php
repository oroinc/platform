<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Gedmo\Translatable\Entity\Repository\TranslationRepository;

/**
 * Abstract Gedmo translation repository for translation dictionaries.
 * It can speed up translation updating process.
 */
abstract class AbstractTranslationRepository extends TranslationRepository
{
    /**
     * @param string $className
     * @param string $fieldName
     * @param array $data
     * @param string $locale
     */
    protected function doUpdateTranslations(string $className, string $fieldName, array $data, string $locale)
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

            foreach ($result as $key => $trans) {
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
}
