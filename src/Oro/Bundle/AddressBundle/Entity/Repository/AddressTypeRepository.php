<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;

/**
 * Entity repository for AddressType dictionary.
 */
class AddressTypeRepository extends EntityRepository implements IdentityAwareTranslationRepositoryInterface
{
    /**
     * @return array
     */
    public function getAllIdentities()
    {
        $result = $this->createQueryBuilder('c')
            ->select('c.name')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'name');
    }

    /**
     * {@inheritdoc}
     */
    public function updateTranslations(array $data, string $locale = null)
    {
        if (!$data) {
            return;
        }

        $connection = $this->getEntityManager()->getConnection();
        $connection->beginTransaction();

        try {
            $qb = $this->createQueryBuilder('a');
            $qb->select('a.name', 'a.label')
                ->where($qb->expr()->in('a.name', ':name'))
                ->setParameter('name', array_keys($data));

            $result = $qb->getQuery()->getArrayResult();

            foreach ($result as $type) {
                $value = $data[$type['name']];

                if ($type['label'] !== $value) {
                    $connection->update(
                        $this->getClassMetadata()->getTableName(),
                        ['name' => $type['name']],
                        ['label' => $value]
                    );
                }
            }

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();

            throw $e;
        }
    }
}
